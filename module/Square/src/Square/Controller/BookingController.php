<?php

namespace Square\Controller;

use Booking\Entity\Booking\Bill;
use RuntimeException;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\ServiceManager\ServiceLocator;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\View\Model\JsonModel;
use Zend\Http\Response;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Reply\ReplyInterface;
use Payum\Stripe\Request\Confirm;
use Stripe;
use GuzzleHttp\Client; 
use \Square\Factory\Cart;
use DateTime;

class BookingController extends AbstractActionController
{

    public function customizationAction()
    {
        $dateStartParam = $this->params()->fromQuery('ds');
        $dateEndParam = $this->params()->fromQuery('de');
        $timeStartParam = $this->params()->fromQuery('ts');
        $timeEndParam = $this->params()->fromQuery('te');
        $squareParam = $this->params()->fromQuery('s');

        $serviceManager = $this->getServiceLocator();
        $squareValidator = $serviceManager->get('Square\Service\SquareValidator');

        $byproducts = $squareValidator->isBookable($dateStartParam, $dateEndParam, $timeStartParam, $timeEndParam, $squareParam);

        $user = $byproducts['user'];

        if (! $user) {
            $query = $this->getRequest()->getUri()->getQueryAsArray();
            $query['ajax'] = 'false';

            $this->redirectBack()->setOrigin('square/booking/customization', [], ['query' => $query]);

            return $this->redirect()->toRoute('user/login');
        }

        if (! $byproducts['bookable']) {
            throw new RuntimeException(sprintf($this->t('This %s is already occupied'), $this->option('subject.square.type')));
        }

        return $this->ajaxViewModel($byproducts);
    }

    public function addToCartAction()
    {
        // Retrieve the booking details from the request parameters
        $dateStartParam = $this->params()->fromQuery('ds');
        $dateEndParam = $this->params()->fromQuery('de');
        $timeStartParam = $this->params()->fromQuery('ts');
        $timeEndParam = $this->params()->fromQuery('te');
        $squareParam = $this->params()->fromQuery('s');
        $quantityParam = $this->params()->fromQuery('q', 1);
        $productsParam = $this->params()->fromQuery('p', 0);
        $playerNamesParam = $this->params()->fromQuery('pn', 0);

        // Validate the booking details and retrieve the necessary services
        $serviceManager = $this->getServiceLocator();
        $squareValidator = $serviceManager->get('Square\Service\SquareValidator');
        $byproducts = $squareValidator->isBookable($dateStartParam, $dateEndParam, $timeStartParam, $timeEndParam, $squareParam);
        $user = $byproducts['user'];

        // Users need to login before adding bookings to cart
        if (! $user) {
            $query = $this->getRequest()->getUri()->getQueryAsArray();
            $query['ajax'] = 'false';

            $this->redirectBack()->setOrigin('square/booking/addtocart', [], ['query' => $query]);

            return $this->redirect()->toRoute('user/login');
        }

        // No longer available
        if (! $byproducts['bookable']) {
            throw new RuntimeException(sprintf($this->t('This %s is already occupied'), $this->option('subject.square.type')));
        }

        /* Check passed quantity */

        if (! (is_numeric($quantityParam) && $quantityParam > 0)) {
            throw new RuntimeException(sprintf($this->t('Invalid %s-amount choosen'), $this->option('subject.square.unit')));
        }

        $square = $byproducts['square'];

        if ($square->need('capacity') - $byproducts['quantity'] < $quantityParam) {
            throw new RuntimeException(sprintf($this->t('Too many %s for this %s choosen'), $this->option('subject.square.unit.plural'), $this->option('subject.square.type')));
        }

        $byproducts['quantityChoosen'] = $quantityParam;

        /* Check passed products */

        $products = array();

        if (! ($productsParam === '0' || $productsParam === 0)) {
            $productManager = $serviceManager->get('Square\Manager\SquareProductManager');
            $productTuples = explode(',', $productsParam);

            foreach ($productTuples as $productTuple) {
                $productTupleParts = explode(':', $productTuple);

                if (count($productTupleParts) != 2) {
                    throw new RuntimeException('Malformed product parameter passed');
                }

                $spid = $productTupleParts[0];
                $amount = $productTupleParts[1];

                if (! (is_numeric($spid) && $spid > 0)) {
                    throw new RuntimeException('Malformed product parameter passed');
                }

                if (! is_numeric($amount)) {
                    throw new RuntimeException('Malformed product parameter passed');
                }

                $product = $productManager->get($spid);

                $productOptions = explode(',', $product->need('options'));

                if (! in_array($amount, $productOptions)) {
                    throw new RuntimeException('Malformed product parameter passed');
                }

                $product->setExtra('amount', $amount);

                $products[$spid] = $product;
            }
        }

        $byproducts['products'] = $products;

        /* Check passed player names */

        if ($playerNamesParam) {
            $playerNames = Json::decode($playerNamesParam, Json::TYPE_ARRAY);

            foreach ($playerNames as $playerName) {
                if (strlen(trim($playerName['value'])) < 5 || strpos(trim($playerName['value']), ' ') === false) {
                    throw new \RuntimeException('Other players <b>full first and last names</b> are required');
                }
            }
        } else {
            $playerNames = null;
        }

        // Store the booking details in the cart
        $cartService = Cart::getInstance();

        // Define the booking info
        $bookingInfo = [
            'square' => $squareParam,
            'start' => $byproducts['dateStart']->format('Y-m-d H:i:s'),
            'end' => $byproducts['dateEnd']->format('Y-m-d H:i:s'),
            'dateStart' => $dateStartParam,
            'dateEnd' => $dateEndParam,
            'timeStart' => $timeStartParam,
            'timeEnd' => $timeEndParam
        ];

        // Check if the booking info already exists in the cart
        $cartItems = $cartService->getItems();
        $itemExists = false;

        $bookingStartTime = new DateTime($bookingInfo['start']);
        $bookingEndTime = new DateTime($bookingInfo['end']);

        syslog(LOG_EMERG, $bookingInfo['start']);
        syslog(LOG_EMERG, $byproducts['dateStart']);

        foreach ($cartItems as $cartItem) {
            if ($cartItem['square'] === $bookingInfo['square']) {
                // Check for time overlap
                $cartStartTime = new DateTime($cartItem['start']);
                $cartEndTime = new DateTime($cartItem['end']);

                if (($bookingStartTime >= $cartStartTime && $bookingStartTime < $cartEndTime) ||
                    ($bookingEndTime > $cartStartTime && $bookingEndTime <= $cartEndTime) ||
                    ($bookingStartTime <= $cartStartTime && $bookingEndTime >= $cartEndTime)) {
                    // There is a time overlap, set $itemExists to true
                    $itemExists = true;
                    break;
                }
            }
        }

        if (!$itemExists) {
            // Add the booking info to the cart only if it doesn't exist already
            $cartService->addToCart($bookingInfo);
        } else {
            $this->flashMessenger()->addErrorMessage(sprintf($this->t('%sThis booking already exists in the cart!%s'),
                       '<b>', '</b>'));
        }

        return $this->redirect()->toRoute('user/cart');
    }

    public function checkoutAction()
    {
        $serviceManager = $this->getServiceLocator();

        // Check user info
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');
        $user = $userSessionManager->getSessionUser();

        // Users need to login before checkout
        if (! $user) {
            $query = $this->getRequest()->getUri()->getQueryAsArray();
            $query['ajax'] = 'false';
            // $this->redirectBack()->setOrigin('square/booking/checkout');
            return $this->redirect()->toRoute('user/login');
        }

        // Retrieve the booking details from the cart
        $cartService = Cart::getInstance();
        $cartItems = $cartService->getItems();

        //clear cart if the square is dated old
        // Get the current datetime
        $current_datetime = date('Y-m-d H:i:s'); // Format it as 'YYYY-MM-DD HH:MM:SS'

        // // Iterate through the $cartItem array and remove items where "start" time is before the current datetime
        // foreach ($cartItems as $key => $item) {
        //     $start_time = $item['start'];
        //     if ($start_time < $current_datetime) {
        //         unset($cartItems[$key]);
        //     }
        // }

        // // Re-index the array if needed
        // $cartItems = array_values($cartItems);

        // syslog(LOG_EMERG, 'printing cart in checkout action');
        // syslog(LOG_EMERG, json_encode($cartItems));

        // Check if the user is a member
        $member = 0;
        if ($user != null && $user->getMeta('member') != null) {
           $member = $user->getMeta('member');
        }

        // Create an array to store items that are still available
        $updatedCartItems = [];
        $allAvailable = True;
        $total = 0;

        // Check if each square still available
        $squareValidator = $serviceManager->get('Square\Service\SquareValidator');
        $squarePricingManager = $serviceManager->get('Square\Manager\SquarePricingManager');
        foreach ($cartItems as &$cartItem) {

            $is_bookable = false;
            try {
                $byproducts = $squareValidator->isBookable($cartItem['dateStart'], $cartItem['dateEnd'], $cartItem['timeStart'], $cartItem['timeEnd'], $cartItem['square']);
                $is_bookable = $byproducts['bookable'];
            } catch (RuntimeException $e) {
                $is_bookable = false;
            }
            
            // If the booking is no longer available
            if (! $is_bookable) {
                $this->flashMessenger()->addErrorMessage(sprintf($this->t('%sA booking is no longer available!%s'),
                       '<b>', '</b>'));
                $allAvailable = False;
            } else {
                // Get square and user info
                $finalPrice = $squarePricingManager->getFinalPricingInRange($byproducts['dateStart'], $byproducts['dateEnd'], $byproducts['square'], 1, $member);
                $total += $finalPrice['price'];

                // Store price
                $cartItem['price'] = $finalPrice['price'];

                // Add to updated cart
                $updatedCartItems[] = $cartItem;
            }
        }

        // printing byproducts
        syslog(LOG_EMERG, 'printing byproducts');
        syslog(LOG_EMERG, json_encode($byproducts));

        // If not all available, warn user and refresh cart
        if(! $allAvailable) {
            // Update the cart with the available items
            $cartService->setItems($updatedCartItems);

            // Redirect back to the cart page after cleanup
            return $this->redirect()->toRoute('user/cart');
        }
        
        $payable = false;

        // // billing for products??? (need to check later)
         //$products = $byproducts['products'];

         $bills = array();
        //  foreach ($products as $product) {

        //     $bills[] = new Bill(array(
        //        'description' => $product->need('name'),
        //        'quantity' => $product->needExtra('amount'),
        //        'price' => $product->need('price') * $product->needExtra('amount'),
        //        'rate' => $product->need('rate'),
        //        'gross' => $product->need('gross'),
        //     ));

        //     $total+=$product->need('price') * $product->needExtra('amount');
        // }

        $newbudget = 0;
        $byproducts['hasBudget'] = false; 
        $budgetpayment = false;

        // calculate end total from user budget
        if ($user != null && $user->getMeta('budget') != null && $user->getMeta('budget') > 0 && $total > 0) {
            // Get user budget (credit)
            $byproducts['hasBudget'] = true;
            $budget = $user->getMeta('budget');
            $byproducts['budget'] = $budget;

            // Calculate new total payable
            $newtotal = $total - ($budget*100);
            if ($newtotal <= 0) {
                $budgetpayment = true;
            }
            $byproducts['newtotal'] = $newtotal;

            // Calculate new budget
            $newbudget = ($budget*100-$total)/100;
            if ($newbudget < 0) { 
                $newbudget = 0;
            }
            $byproducts['newbudget'] = $newbudget;

            $total = $newtotal;
        }

        // Check if there are remaining amounts payable
        if ($total > 0 ) {
            $payable = true;
        }
        $byproducts['payable'] = $payable;

        /* Check booking form submission */

        // $acceptRulesDocument = $this->params()->fromPost('bf-accept-rules-document');
        // $acceptRulesText = $this->params()->fromPost('bf-accept-rules-text');
        $confirmationHash = sha1('Quick and dirty' . floor(time() / 1800));
        $confirmationHashOriginal = sha1('Quick and dirty' . floor(time() / 1800));

        if ($confirmationHash) {

            // Check if users accepted the rules associated with the squares

            // if ($square->getMeta('rules.document.file') && $acceptRulesDocument != 'on') {
            //     $byproducts['message'] = sprintf($this->t('%sNote:%s Please read and accept the "%s".'),
            //         '<b>', '</b>', $square->getMeta('rules.document.name', 'Rules-document'));
            // }

            // if ($square->getMeta('rules.text') && $acceptRulesText != 'on') {
            //     $byproducts['message'] = sprintf($this->t('%sNote:%s Please read and accept our rules and notes.'),
            //         '<b>', '</b>');
            // }

            // if ($confirmationHash != $confirmationHashOriginal) {
            //     $byproducts['message'] = sprintf($this->t('%We are sorry:%s This did not work somehow. Please try again.'),
            //         '<b>', '</b>');
            // }

          if (! isset($byproducts['message'])) {

            $bookingService = $serviceManager->get('Booking\Service\BookingService');
            $bookingManager = $serviceManager->get('Booking\Manager\BookingManager');

            // iterate through each square and setup a booking
            $bookings = array();
            $booking_str = "";
            
            foreach ($cartItems as &$cartItem) {
                // get square
                $byproducts = $squareValidator->isBookable($cartItem['dateStart'], $cartItem['dateEnd'], $cartItem['timeStart'], $cartItem['timeEnd'], $cartItem['square']);
                $square = $byproducts['square'];

                // Add notes
                $notes = ''; 
                // if ($square->get('allow_notes') && $this->params()->fromPost('bf-user-notes') != null && $this->params()->fromPost('bf-user-notes') != '') {
                //     $notes = "Anmerkungen des Benutzers:\n" . $this->params()->fromPost('bf-user-notes') . " || ";
                // } 
                // $payservice = $this->params()->fromPost('paymentservice');
                $payservice = 'stripe';
                // $meta = array('player-names' => serialize($playerNames), 'notes' => $notes);
                $meta = array('player-names' => serialize(''), 'notes' => $notes);  
                
                // Check payment method
                if (($payservice == 'paypal' || $payservice == 'stripe' || $payservice == 'klarna') && $payable) {
                       $meta['directpay'] = 'true';
                }

                //printing data to create booking
                syslog(LOG_EMERG, 'printing data to create booking');
                syslog(LOG_EMERG, json_encode($user));
                syslog(LOG_EMERG, json_encode($square));
                syslog(LOG_EMERG, json_encode($quantityParam));
                syslog(LOG_EMERG, json_encode($byproducts['dateStart']));
                syslog(LOG_EMERG, json_encode($byproducts['dateEnd']));
                syslog(LOG_EMERG, json_encode($bills));
                syslog(LOG_EMERG, json_encode($meta));
                syslog(LOG_EMERG, 'printing data to create booking - end');

                // Create booking and add to list
                $bookings[] = array('b' => $bookingService->createSingle($user, $square, 1, $byproducts['dateStart'], $byproducts['dateEnd'], $bills, $meta),
                                    'p' => $cartItem['price']);

                $booking_str = $booking_str . 'Court ' . $square->get("name") . ' ' . $byproducts['dateStart']->format('Y-m-d H:i:s') . ' - ' . $byproducts['dateEnd']->format('Y-m-d H:i:s') . "<br>";
            }

            syslog(LOG_EMERG, 'printing bookings');
            syslog(LOG_EMERG, json_encode($bookings));

            /* Go to payment */
            if (($payservice == 'paypal' || $payservice == 'stripe' || $payservice == 'klarna') && $payable) {
            # payment checkout
                if($payable) {

                   $basepath = $this->config('basepath');
                   if (isset($basepath) && $basepath != '' && $basepath != ' ') {
                       $basepath = '/'.$basepath;  
                   } 
                   $projectShort = $this->option('client.name.short');
                   $baseurl = $this->config('baseurl');
                   $proxyurl = $this->config('proxyurl');
		           $storage = $this->getServiceLocator()->get('payum')->getStorage('Application\Model\PaymentDetails');
                   $tokenStorage = $this->getServiceLocator()->get('payum.options')->getTokenStorage(); 
                   $captureToken = null;
                   $model = $storage->create();

                   // setup description and names
                   $description = $projectShort.' booking-';
                   $userName = $user->getMeta('firstname') . ' ' . $user->getMeta('lastname');
                   $companyName = $this->option('client.name.full');
                   $locale = $this->config('i18n.locale');

                   // Get user budget
                   $budget = $user->getMeta('budget');

                   // add booking meta info
                   foreach ($bookings as &$tuple) {
                        $booking = $tuple['b'];
                        $bookingamt = $tuple['p'];

                        // recalculate the budget for each booking
                        $hasBudget = false;
                        if ($budget > 0) {
                            $hasBudget = true;
                        }
                        $newbudget = ($budget*100-$bookingamt)/100;
                        if ($newbudget < 0) { 
                            $newbudget = 0;
                        }

                        // save booking info
                        $booking->setMeta('paymentMethod', $payservice);
                        $booking->setMeta('hasBudget', $hasBudget);
                        $booking->setMeta('newbudget', $newbudget);
                        $booking->setMeta('budget', $budget);
                        $bookingManager->save($booking);

                        // update budget
                        $budget = $newbudget;

                        // add booking id to description
                        $description = $description.$booking->get('bid').'_';
                   }
                   // remove extra comma
                   $description = rtrim($description, '_');

                   #stripe checkout
                   if ($payservice == 'stripe') {
                       $model["payment_method_types"] = $this->config('stripePaymentMethods');
                       $model["amount"] = $total;
                       $model["currency"] = 'AUD';
                       $model["description"] = $description;
                       $model["receipt_email"] = $user->get('email');
                       $model["metadata"] = array('bid' => $booking->get('bid'), 'productName' => $booking_str, 'locale' => $locale, 'instance' => $basepath, 'projectShort' => $projectShort, 'userName' => $userName, 'companyName' => $companyName, 'stripeDefaultPaymentMethod' => $this->config('stripeDefaultPaymentMethod'), 'stripeAutoConfirm' => var_export($this->config('stripeAutoConfirm'), true), 'stripePaymentRequest' => var_export($this->config('stripePaymentRequest'), true));
                       $storage->update($model);
                       $captureToken = $this->getServiceLocator()->get('payum.security.token_factory')->createCaptureToken(
                           'stripe', $model, $proxyurl.$basepath.'/square/booking/payment/confirm');
                   }
                   #stripe checkout
                   
                   $targetUrl = str_replace($baseurl, $proxyurl, $captureToken->getTargetUrl());
                   $captureToken->setTargetUrl($targetUrl);
                   $tokenStorage->update($captureToken);

                    syslog(LOG_EMERG, 'End of checkout action');
                   return $this->redirect()->toUrl($captureToken->getTargetUrl());
                   }
                else {
                   // Cancel all bookings
                   foreach ($bookings as &$tuple) {
                        $booking = $tuple['b'];
                        $bookingService->cancelSingle($booking);
                   }
                   $this->flashMessenger()->addErrorMessage(sprintf($this->t('%sSorry online booking not possible at the moment!%s'),
                       '<b>', '</b>'));
                   return $this->redirectBack()->toOrigin();  
                }
                # payment checkout
            } else {
                # no paymentservice
                # redefine user budget
                if ($budgetpayment) { 
                    $userManager = $serviceManager->get('User\Manager\UserManager');

                    // update user budget
                    $user->setMeta('budget', $newbudget);
                    $userManager->save($user);

                    foreach ($bookings as &$tuple) {
                        $booking = $tuple['b'];
                        $bookingamt = $tuple['p'];
                        // update booking info
                        $booking->setMeta('budget', $budget);
                        $booking->setMeta('newbudget', $newbudget);
                        $booking->set('status_billing', 'paid');
                        $notes = $notes . " payment with user budget";
                        $booking->setMeta('notes', $notes);
                        $bookingManager->save($booking);
                    }

                    if ($this->config('tmpBookingAt') != null) {    
                        $this->flashMessenger()->addSuccessMessage(sprintf($this->t('%sPayment and admittance temporarily at %s!%s'),
                            '<b>', $this->config('tmpBookingAt'), '</b>'));
                    }

                    // Clear cart
                    $cartService = Cart::getInstance();
                    $cartService->setItems([]);

                    return $this->redirectBack()->toOrigin();
                }
          }
        }

        // Clear cart
        $cartService->setItems([]);
        return $this->ajaxViewModel($byproducts);
    }
}

    public function cancellationAction()
    {
        $serviceManager = $this->getServiceLocator();
        $bookingManager = $serviceManager->get('Booking\Manager\BookingManager');
        $bookingBillManager = $serviceManager->get('Booking\Manager\Booking\BillManager');
        $squareValidator = $serviceManager->get('Square\Service\SquareValidator');

        // get bids
        $bid = $this->params()->fromQuery('bid');
        $confirmed = $this->params()->fromQuery('confirmed');

        // iterate through bookings
        preg_match_all('/\d+/', $bid, $matches);
        $bids = $matches[0];
        syslog(LOG_EMERG, json_encode($bids));
        foreach ($bids as $bid) {

            if (! (is_numeric($bid) && $bid > 0)) {
                throw new RuntimeException('This booking does not exist');
            }

            $booking = $bookingManager->get($bid);

            $cancellable = $squareValidator->isCancellable($booking);

            if (! $cancellable) {
                throw new RuntimeException('This booking cannot be cancelled anymore online.');
            }

            $origin = $this->redirectBack()->getOriginAsUrl();

            /* Check cancellation confirmation */
            if ($confirmed == 'true') {

                $bookingService = $serviceManager->get('Booking\Service\BookingService');
    
                $userManager = $serviceManager->get('User\Manager\UserManager');
                $user = $userManager->get($booking->get('uid'));
    
                $bookingService->cancelSingle($booking);
    
                # redefine user budget if status paid
                if ($booking->need('status') == 'cancelled' && $booking->get('status_billing') == 'paid' && !$booking->getMeta('refunded') == 'true') {
                    $booking->setMeta('refunded', 'true');
                    $bookingManager->save($booking);
                    $bills = $bookingBillManager->getBy(array('bid' => $booking->get('bid')), 'bbid ASC');
                    $total = 0;
                    if ($bills) {
                        foreach ($bills as $bill) {
                            $total += $bill->need('price');
                        }
                    }
                
                    $olduserbudget = $user->getMeta('budget');
                    if ($olduserbudget == null || $olduserbudget == '') {
                        $olduserbudget = 0;
                    }
    
                    $newbudget = ($olduserbudget*100+$total)/100;
    
                    $user->setMeta('budget', $newbudget);
                    $userManager->save($user);
                }

            }
        }

        if ($confirmed == 'true') {
            $this->flashMessenger()->addErrorMessage(sprintf($this->t('Your booking has been %scancelled%s.'),
            '<b>', '</b>'));
            return $this->redirectBack()->toOrigin();
        }
        
        return $this->ajaxViewModel(array(
            'bid' => $bid,
            'origin' => $origin,
        ));
    }

    public function confirmAction()
    {

        $token = $this->getServiceLocator()->get('payum.security.http_request_verifier')->verify($this);
        $gateway = $this->getServiceLocator()->get('payum')->getGateway($token->getGatewayName());
        $tokenStorage = $this->getServiceLocator()->get('payum.options')->getTokenStorage();
        $gateway->execute($status = new GetHumanStatus($token));

        $payment = $status->getFirstModel();
        syslog(LOG_EMERG, 'confirm action triggered');

         syslog(LOG_EMERG, $payment['status']);
         syslog(LOG_EMERG, json_encode($payment));

        if (($payment['status'] == "requires_action" && !(array_key_exists('error',$payment)))) {
            
          $payment['doneAction'] = $token->getTargetUrl();

           try {
                syslog(LOG_EMERG, "executing confirm");

               $gateway->execute(new Confirm($payment));

                syslog(LOG_EMERG, $payment['status']);
                syslog(LOG_EMERG, json_encode($payment));

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
            syslog(LOG_EMERG, json_encode($payment)); 
            syslog(LOG_EMERG, $payment['status']); 
            syslog(LOG_EMERG, "confirm error");
           $doneAction = str_replace("confirm", "done", $token->getTargetUrl());

           $token->setTargetUrl($doneAction);
           $tokenStorage->update($token);
           return $this->redirect()->toUrl($doneAction);
        }

    }    

    public function doneAction()
    {
         syslog(LOG_EMERG, 'doneAction');
        
        $serviceManager = $this->getServiceLocator();
        $bookingManager = $serviceManager->get('Booking\Manager\BookingManager');
        $squareManager = $serviceManager->get('Square\Manager\SquareManager');
        $squareValidator = $serviceManager->get('Square\Service\SquareValidator');
        $bookingService = $serviceManager->get('Booking\Service\BookingService');

        $token = $serviceManager->get('payum.security.http_request_verifier')->verify($this);
        $gateway = $serviceManager->get('payum')->getGateway($token->getGatewayName());
        $gateway->execute($status = new GetHumanStatus($token));
        $payment = $status->getFirstModel();
        
        syslog(LOG_EMERG, json_encode($status));

        // Retrieve the booking details from the cart
        $cartService = Cart::getInstance();
        $cartItems = $cartService->getItems();

        syslog(LOG_EMERG, 'print cart in done action');
        syslog(LOG_EMERG, json_encode($cartItems));
        syslog(LOG_EMERG, 'print payment in done action');
        syslog(LOG_EMERG, json_encode($payment));

        $origin = $this->redirectBack()->getOriginAsUrl();

        $bid = -1;  
        $paymentNotes = '';        

#stripe
        if ($token->getGatewayName() == 'stripe') {
            $bid = $payment['metadata']['bid'];
            $paymentNotes = ' direct pay with stripe ' . $payment['charges']['data'][0]['payment_method_details']['type'] . ' - ';
        }
#stripe

        // iterate through bookings
        preg_match_all('/\d+/', $payment['description'], $matches);
        $bids = $matches[0];
        syslog(LOG_EMERG, json_encode($bids));
        foreach ($bids as $bid) {
            if (! (is_numeric($bid) && $bid > 0)) {
                throw new RuntimeException('This booking does not exist');
            }
    
            $booking = $bookingManager->get($bid);
            $notes = $booking->getMeta('notes');
    
            syslog(LOG_EMERG, 'print booking manager');
            syslog(LOG_EMERG,$booking);
            syslog(LOG_EMERG,json_encode($booking));
    
            $notes = $notes . $paymentNotes;
    
            $square = $squareManager->get($booking->need('sid'));

            if ($status->isCaptured() || $status->isAuthorized() || $status->isPending() || ($status->isUnknown() && $payment['status'] == 'processing') || $status->getValue() === "success" || $payment['status'] === "succeeded" ) {

                syslog(LOG_EMERG, 'doneAction - success');
               
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
                        syslog(LOG_EMERG, 'success not pending');
                       $this->flashMessenger()->addSuccessMessage(sprintf($this->t('%sCongratulations:%s Your %s has been booked!'),
                           '<b>', '</b>',$this->option('subject.square.type')));
                   }
               }
   
               if($status->isPending() || ($status->isUnknown() && $payment['status'] == 'processing')) {
                    syslog(LOG_EMERG, 'success pending/processing');
                   $booking->set('status_billing', 'pending');
                   $booking->setMeta('directpay', 'false');
                   $booking->setMeta('directpay_pending', 'true');
                    syslog(LOG_EMERG, 'success not pending');
               }
               else { // need to do this to all items in the cart
                   
                    syslog(LOG_EMERG, 'success paid');
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
                   $notes = $notes . " payment with user budget | ";
               }
   
               $notes = $notes . " payment_status: " . $status->getValue() . ' ' . $payment['status'];
               $booking->setMeta('notes', $notes);
               $bookingService->updatePaymentSingle($booking);
           }
           else
           {
                syslog(LOG_EMERG, 'doneAction - error');
               
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
        }

        // Clear cart
        $cartService = Cart::getInstance();
        $cartService->setItems([]);

        return $this->redirectBack()->toOrigin();
   
    }

}
