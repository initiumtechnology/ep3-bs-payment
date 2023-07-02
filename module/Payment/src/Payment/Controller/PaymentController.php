<?php

namespace Payment\Controller;

use DateTime;
use RuntimeException;
use Zend\Crypt\Password\Bcrypt;
use Zend\Mvc\Controller\AbstractActionController;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Reply\ReplyInterface;
use Payum\Stripe\Request\Confirm;
use Stripe;
use GuzzleHttp\Client;

class PaymentController extends AbstractActionController
{

    public function confirmAction()
    {

        $token = $this->getServiceLocator()->get('payum.security.http_request_verifier')->verify($this);
        $gateway = $this->getServiceLocator()->get('payum')->getGateway($token->getGatewayName());
        $tokenStorage = $this->getServiceLocator()->get('payum.options')->getTokenStorage();
        $gateway->execute($status = new GetHumanStatus($token));

        $payment = $status->getFirstModel();

        // syslog(LOG_EMERG, $payment['status']);

        if ($payment['status'] === "requires_action" && !(array_key_exists('error',$payment))) {

           $payment['paymentDoneAction'] = $token->getTargetUrl();

           try {
               $gateway->execute(new Confirm($payment));

           } catch (ReplyInterface $reply) {
               if ($reply instanceof HttpRedirect) {
                  return $this->redirect()->toUrl($reply->getUrl());
               }
               if ($reply instanceof HttpResponse) {
                  $this->getResponse()->setContent($reply->getContent());
                  $response = new Response();
                  $response->setStatusCode(200);
                  $response->setContent($reply->getContent());
                  return $response;
               }
            throw new \LogicException('Unsupported reply', null, $reply);
            }

        }

        if ($payment['status'] != "requires_action" || array_key_exists('error',$payment)) {
           $doneAction = str_replace("paymentConfirm", "paymentDone", $token->getTargetUrl());

           $token->setTargetUrl($doneAction);
           $tokenStorage->update($token);
           return $this->redirect()->toUrl($doneAction);
        }

    }

    
    public function doneAction()
    {
        // syslog(LOG_EMERG, 'doneAction');
        
        $serviceManager = $this->getServiceLocator();
        $bookingManager = $serviceManager->get('Booking\Manager\BookingManager');
        $squareManager = $serviceManager->get('Square\Manager\SquareManager');

        $bookingService = $serviceManager->get('Booking\Service\BookingService');

        $token = $serviceManager->get('payum.security.http_request_verifier')->verify($this);

        $gateway = $serviceManager->get('payum')->getGateway($token->getGatewayName());

        $gateway->execute($status = new GetHumanStatus($token));

        $payment = $status->getFirstModel();

        $origin = $this->redirectBack()->getOriginAsUrl();

        $bid = -1;
        $paymentNotes = '';
#paypal
        if ($token->getGatewayName() == 'paypal_ec') {
            $bid = $payment['PAYMENTREQUEST_0_BID'];
            $paymentNotes = ' direct pay with paypal - ';
        }
#paypal
#eway
        if ($token->getGatewayName() == 'omnipay') {
            $bid = $payment['PAYMENTREQUEST_0_BID'];
            $paymentNotes = ' direct pay with eway - ';
        }

#eway

#stripe
        if ($token->getGatewayName() == 'stripe') {
            $bid = $payment['metadata']['bid'];
            $paymentNotes = ' direct pay with stripe ' . $payment['charges']['data'][0]['payment_method_details']['type'] . ' - ';
        }
#stripe
#klarna
        if ($token->getGatewayName() == 'klarna') {
            $bid = $payment['items']['reference'];
            $paymentNotes = ' direct pay with klarna - ';
        }
#klarna

        if (! (is_numeric($bid) && $bid > 0)) {
            throw new RuntimeException('This booking does not exist');
        }

        $booking = $bookingManager->get($bid);
        $notes = $booking->getMeta('notes');

        $notes = $notes . $paymentNotes;

        $square = $squareManager->get($booking->need('sid'));

        if ($status->isCaptured() || $status->isAuthorized() || $status->isPending() || ($status->isUnknown() && $payment['status'] == 'processing') || $status->getValue() === "success" || $payment['status'] === "succeeded" ) {

            if (!$booking->getMeta('directpay_pending') == 'true') {
                if ($this->config('genDoorCode') != null && $this->config('genDoorCode') == true && $square->getMeta('square_control') == true) {
                   $doorCode = $booking->getMeta('doorCode');
                   $squareControlService = $serviceManager->get('SquareControl\Service\SquareControlService');
                   if ($squareControlService->createDoorCode($bid, $doorCode) == true) {
                       $this->flashMessenger()->addSuccessMessage(sprintf($this->t('Your %s has been booked! The doorcode is: %s'),
                           $this->option('subject.square.type'), $doorCode));
                   } else {
                       $this->flashMessenger()->addErrorMessage(sprintf($this->t('Your %s has been booked! But the doorcode could not be send. Please contact admin by phone - %s'),
                           $this->option('subject.square.type'), $this->option('client.contact.phone')));
                   }
                }
                else {
                    // syslog(LOG_EMERG, 'success not pending');
                    $this->flashMessenger()->addSuccessMessage(sprintf($this->t('%sCongratulations:%s Your %s has been booked!'),
                        '<b>', '</b>',$this->option('subject.square.type')));
                }
            }

            if($status->isPending() || ($status->isUnknown() && $payment['status'] == 'processing')) {
                // syslog(LOG_EMERG, 'success pending/processing');
                $booking->set('status_billing', 'pending');
                $booking->setMeta('directpay', 'false');
                $booking->setMeta('directpay_pending', 'true');
            }
            else {
                // syslog(LOG_EMERG, 'success paid');
                $booking->set('status_billing', 'paid');
                $booking->setMeta('directpay', 'true');
                $booking->setMeta('directpay_pending', 'false');
            }

            # redefine user budget
            if ($booking->getMeta('hasBudget')) {
                $userManager = $serviceManager->get('User\Manager\UserManager');
                $user = $userManager->get($booking->get('uid'));
                $user->setMeta('budget', $booking->getMeta('newbudget'));
                $userManager->save($user);
                # set booking to paid
                $notes = $notes . "payment with user budget | ";
            }

            $notes = $notes . "payment_status: " . $status->getValue() . ' ' . $payment['status'];
            $booking->setMeta('notes', $notes);
            $bookingService->updatePaymentSingle($booking);
        }
        else
        {
            if (!$booking->getMeta('directpay_pending') == 'true') {
                if(isset($payment['error']['message'])) {
                    $this->flashMessenger()->addErrorMessage(sprintf($payment['error']['message'],
                                            '<b>', '</b>'));
                }
                $this->flashMessenger()->addErrorMessage(sprintf($this->t('%sError during payment: Your booking has been cancelled.%s'),
                    '<b>', '</b>'));
            }
            $bookingService->cancelSingle($booking);
        }
        
        return $this->redirectBack()->toOrigin();

    }
 
    public function webhookAction()
    {
        // $this->authorize('admin.booking');
        // authorize is done via stripe webhook secret

        $serviceManager = @$this->getServiceLocator();
        $bookingManager = $serviceManager->get('Booking\Manager\BookingManager');
        $reservationManager = $serviceManager->get('Booking\Manager\ReservationManager');
        $squareManager = $serviceManager->get('Square\Manager\SquareManager');
        $squareControlService = $serviceManager->get('SquareControl\Service\SquareControlService');

        // $bookingService = $serviceManager->get('Booking\Service\BookingService');

        $squareControlService->removeInactiveDoorCodes();

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $this->config('stripeWebhookSecret')
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            // syslog(LOG_EMERG, '|UnexpectedValueException|');
            http_response_code(400);
            return false;
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            // syslog(LOG_EMERG, '|invalid signature|');
            http_response_code(400);
            return false;
        }

        // syslog(LOG_EMERG, '|'.$event.'|');

        $bid = -1;
        $intent = null;

        if ($event->type == "payment_intent.succeeded" || $event->type == "payment_intent.payment_failed" || $event->type == "payment_intent.canceled") {
            $intent = $event->data->object;
            $bid = $intent->metadata->bid;
        }
        else {
            http_response_code(400);
            return false;
        }

        // test
        // $bid='1443';
        // $event->type="payment_intent.payment_failed";
        // end test

        // syslog(LOG_EMERG, '|'.$bid.'|');

        if (! (is_numeric($bid) && $bid > 0)) {
            // syslog(LOG_EMERG, 'This bid does not exist');
            http_response_code(400);
            return false;
        }

        try {
            $booking = $bookingManager->get($bid);
            $square = $squareManager->get($booking->get('sid'));
            $notes = $booking->getMeta('notes');

            if ($booking->getMeta('directpay_pending') == true && $booking->getMeta('paymentMethod') == 'stripe') {

            $notes = $notes . " " . " -> via webhook ";

            if ($event->type == "payment_intent.succeeded") {
                // syslog(LOG_EMERG, "Succeeded paymentIntent");
                $notes = $notes . " " . "-> paymentIntent succeded";
                $booking->set('status_billing', 'paid');
                $booking->setMeta('paidAt', date('Y-m-d H:i:s'));

            } elseif ($event->type == "payment_intent.payment_failed" || $event->type == "payment_intent.canceled") {
                // syslog(LOG_EMERG, "Failed or canceled paymentIntent");
                $notes = $notes . " " . "-> paymentIntent failed or canceled";
                $error_message = $intent->last_payment_error ? $intent->last_payment_error->message : "";
                $notes = $notes . " -  " . $error_message;

                // deactivate door code
                if ($this->config('genDoorCode') != null && $this->config('genDoorCode') == true && $square->getMeta('square_control') == true) {
                    $squareControlService->deactivateDoorCode($bid);
                }

                // maybe if booking is not outdated cancel single bookings
                $cancellable = false;
                $reservations = $reservationManager->getBy(array('bid' => $bid), 'date ASC, time_start ASC');
                $reservation = current($reservations);
                if ($reservation) {
                    $reservationStartDate = new DateTime($reservation->need('date') . ' ' . $reservation->need('time_start'));
                    $reservationCancelDate = new DateTime();
                    if ($reservationStartDate > $reservationCancelDate) { $cancellable = true; }
                }

                if ($booking->get('status') == 'single' && $cancellable && $this->config('stripeWebhookCancel') == true) {
                    $booking->set('status', 'cancelled');
                    $booking->setMeta('cancellor', 'stripe');
                    $booking->setMeta('cancelled', date('Y-m-d H:i:s'));
                }
            }

            $booking->setMeta('notes', $notes);
            $bookingManager->save($booking);
            http_response_code(200);
            return true;

            }

        } catch(RuntimeException $e) {
            syslog(LOG_EMERG, $e->getMessage());
            http_response_code(400);
            return false;
        }

        return false;
    }
 
}

