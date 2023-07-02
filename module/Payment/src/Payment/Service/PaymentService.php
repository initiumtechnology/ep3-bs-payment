<?php

namespace Payment\Service;

use Base\Manager\ConfigManager;
use Base\Manager\OptionManager;
use Base\Service\AbstractService;
use Booking\Manager\BookingManager;
use Booking\Manager\ReservationManager;

class PaymentService extends AbstractService
{

    protected $configManager;
    protected $optionManager;
    protected $bookingManager;
    protected $reservationManager;

    public function __construct(ConfigManager $configManager, OptionManager $optionManager, BookingManager $bookingManager, ReservationManager $reservationManager)
    {
        $this->configManager = $configManager;
        $this->optionManager = $optionManager;
        $this->bookingManager = $bookingManager;
        $this->reservationManager = $reservationManager;
    }

    public function initBookingPayment($booking, $user, $payservice, $total, $byproducts)
    {
        $basepath = $this->configManager->need('basepath');
        if (isset($basepath) && $basepath != '' && $basepath != ' ') {
            $basepath = '/'.$basepath;
        }
        $projectShort = $this->optionManager->need('client.name.short');
        $baseurl = $this->configManager->need('baseurl');
        $proxyurl = $this->configManager->need('proxyurl');
        $storage = $this->getServiceLocator()->get('payum')->getStorage('Application\Model\PaymentDetails');
        $tokenStorage = $this->getServiceLocator()->get('payum.options')->getTokenStorage();
        $captureToken = null;
        $model = $storage->create();
        $booking->setMeta('paymentMethod', $payservice);
        $booking->setMeta('hasBudget', $byproducts['hasBudget']);
        $booking->setMeta('newbudget', $byproducts['newbudget']);
        $bookingManager->save($booking);
        $userName = $user->getMeta('firstname') . ' ' . $user->getMeta('lastname');
        $companyName = $this->optionManager->need('client.name.full');

        $locale = $this->configManager->need('i18n.locale');

        $description = $projectShort.' booking-'.$booking->get('bid');
        if (isset($locale) && ($locale == 'de-DE' || $locale == 'de_DE')) {
            $description = $projectShort.' Buchung-'.$booking->get('bid');
        }

        #paypal checkout
        if ($payservice == 'paypal') {
            $model['PAYMENTREQUEST_0_CURRENCYCODE'] = 'EUR';
            $model['PAYMENTREQUEST_0_AMT'] = $total/100;
            $model['PAYMENTREQUEST_0_BID'] = $booking->get('bid');
            $model['PAYMENTREQUEST_0_DESC'] = $description;
            $model['PAYMENTREQUEST_0_EMAIL'] = $user->get('email');
            $storage->update($model);
            $captureToken = $this->getServiceLocator()->get('payum.security.token_factory')->createCaptureToken(
                'paypal_ec', $model, $proxyurl.$basepath.'/payment/booking/done');
        }
        #paypal checkout
        //https://eway.io/api-v3/?php#direct-connection
        #eway checkout
        if ($payservice == 'omnipay') {
            $model['CurrencyCode'] = 'AUD';
            $model['TotalAmount'] = $total/100;
            $model['InvoiceNumber'] = $booking->get('bid');
            $model['InvoiceDescription'] = $description;
            $model['Email'] = $user->get('email');
            $storage->update($model);
            $captureToken = $this->getServiceLocator()->get('payum.security.token_factory')->createCaptureToken(
                'omnipay', $model, $proxyurl.$basepath.'/payment/booking/confirm');
        }
        // so basically it goes from payum.security.token_factory -> payum_capture_do -> /payment/capture[/:payum_token]',
        #eway checkout


        #stripe checkout
        if ($payservice == 'stripe') {
            $model["payment_method_types"] = $this->configManager->need('stripePaymentMethods');
            $model["amount"] = $total;
            $model["currency"] = 'AUD';
            $model["description"] = $description;
            $model["receipt_email"] = $user->get('email');
            $model["metadata"] = array('bid' => $booking->get('bid'), 'productName' => $this->optionManager->need('subject.type'), 'locale' => $locale, 'instance' => $basepath, 'projectShort' => $projectShort, 'userName' => $userName, 'companyName' => $companyName, 'stripeDefaultPaymentMethod' => $this->configManager->need('stripeDefaultPaymentMethod'), 'stripeAutoConfirm' => var_export($this->configManager->need('stripeAutoConfirm'), true), 'stripePaymentRequest' => var_export($this->configManager->need('stripePaymentRequest'), true));
            $storage->update($model);
            $captureToken = $this->getServiceLocator()->get('payum.security.token_factory')->createCaptureToken(
                'stripe', $model, $proxyurl.$basepath.'/payment/booking/confirm');
        }
        #stripe checkout
        #klarna checkout
        if ($payservice == 'klarna') {
            $model['purchase_country'] = 'DE';
            $model['purchase_currency'] = 'EUR';
            $model['locale'] = 'de-DE';
            $storage->update($model);
            $captureToken = $this->getServiceLocator()->get('payum.security.token_factory')->createAuthorizeToken('klarna_checkout', $model, $proxyurl.$basepath.'/payment/booking/done');
            $notifyToken = $this->getServiceLocator()->get('payum.security.token_factory')->createNotifyToken('klarna_checkout', $model);
        }
        #klarna checkout

        $targetUrl = str_replace($baseurl, $proxyurl, $captureToken->getTargetUrl());
        $captureToken->setTargetUrl($targetUrl);
        $tokenStorage->update($captureToken);

        #klarna checkout update merchant details
        if ($payservice == 'klarna') {
            $model['merchant'] = array(
                'terms_uri' => 'http://example.com/terms',
                'checkout_uri' => $captureToken->getTargetUrl(),
                'confirmation_uri' => $captureToken->getTargetUrl(),
                'push_uri' => $notifyToken->getTargetUrl()
            );
            $model['cart'] = array(
                'items' => array(
                    array(
                        'reference' => $booking->get('bid'),
                        'name' => $description,
                        'quantity' => 1,
                        'unit_price' => $total,
                    )
                )
            );
            $storage->update($model);
        }
        #klarna checkout

        return $this->redirect()->toUrl($captureToken->getTargetUrl());

    }    

}
