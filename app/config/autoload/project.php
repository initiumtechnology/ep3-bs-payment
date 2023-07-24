<?php
/**
 * project application configuration
 *
 */


$instance = 'public';

return array(
    // basepath of the instance on your server - i.e. tvas-booking-dev for https://tennis-rudolstadt.de/tvas-booking-dev or '' if you don't have a basepath square/ i.e. 
    // if you don't point to your public dir directly as described in the install doc of the original project you have to point here to $instance."/public" 
    'basepath' => $instance,
    // origin url of your server - http://yourip - if  you don't have a proxy, baseurl and proxyurl should be the same like https://tennis-rudolstadt.de
    'baseurl' => 'http://localhost:8000',
    // the proxy url - i.e. https://tennis-rudolstadt.de
    'proxyurl' => 'http://localhost:8000',
    'cookie_config' => array(
        'cookie_name_prefix' => $instance,
    ),
    'redirect_config' => array(
        'cookie_name' => $instance.'-origin',
        'default_origin' => 'frontend',
    ),
    'session_config' => array(
        'name' => $instance.'-session',
        'save_path' => getcwd() . '/data/session/',
        'use_cookies' => true,
        'use_only_cookies' => true,
    ),
    // enable /disable door code system
    'genDoorCode' => false,
    // add a buffer to the booked time period for validity of the door code
    'doorCodeTimeBuffer' => '15 minutes',
    // API http request for your door system
    'getDoorCodesRequest' => 'http://admin:password@Miniserver/jdev/sps/io/Module_Uuid/codes',
    'createDoorCodeRequest' => 'http://admin:password@Miniserver/jdev/sps/io/Module_Uuid/code/create/booking-%%bid%%/%%doorCode%%/2/1/1/%%timeFrom%%/%%timeTo%%',
    'deactivateDoorCodeRequest' => 'http://admin:password@Miniserver/jdev/sps/io/Module_Uuid/code/deactivate/%%doorCodeUuid%%',
    'deleteDoorCodeRequest' => 'http://admin:password@Miniserver/jdev/sps/io/Module_Uuid/code/delete/%%doorCodeUuid%%',
    // show a flash message where you can manually book / pay temporarily if you disable all payment methods
    // 'tmpBookingAt' => '?',
    // enable/disable specific payment providers 
    'paypal' => false,
    'stripe' => true,
    // enable/disable stripe payment methods (possible values: card, sepa_debit, ideal, giropay, sofort)
    'stripePaymentMethods' => array(
        'card',
        //'sepa_debit'
    ),
    // enable/disable stripe PaymentRequest (pmr) API for apple / google pay  
    'stripePaymentRequest' => 'true',
    // select the suitable icon for the selected stripe payment methods from imgs-client/layout/card_sepa  
    'stripeIcon' => 'card_ideal_sepa.png',
    // select a default method 
    'stripeDefaultPaymentMethod' => 'card',
    // some payment methods require a confirmation process - select if you want to automate the click on 'authorize' and 'confirm' button
    'stripeAutoConfirm' => 'false',
    // stripe webhook secret for update of pending PaymentIntents
    'stripeWebhookSecret' => '',
    // cancel automatic the booking if payment_intent from stripe fails
    'stripeWebhookCancel' => true, 
    // not yet fully implemented
    'klarna' => false,
    // classic behaviour of ep3-bs with booking on bill 
    'billing' => false,
    // select which payment provider should be activated as default 
    'payment_default' => 'stripe',
);
