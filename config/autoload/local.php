<?php
/**
 * Local application configuration
 *
 * Insert your local database credentials here
 * and provide the email address the system should use.
 */

$detailsClass = 'Application\Model\PaymentDetails';

$project_config = require 'config/autoload/project.php';

return array_merge($project_config, array(
    'db' => array(
        'database' => 'ep3-charlotte',
        'username' => 'root',
        'password' => 'root',

        'hostname' => 'localhost',
        'port' => null,
    ),
    'mail' => array(
        'type' => 'sendmail', // or 'smtp' or 'smtp-tls'
        'address' => 'info@localhost',
            // Make sure 'bookings.example.com' matches the hosting domain when using type 'sendmail'

        'host' => '?', // for 'smtp' type only, otherwise remove or leave as is
        'user' => '?', // for 'smtp' type only, otherwise remove or leave as is
        'pw' => '?', // for 'smtp' type only, otherwise remove or leave as is

        'port' => 'auto', // for 'smtp' type only, otherwise remove or leave as is
        'auth' => 'plain', // for 'smtp' type only, change this to 'login' if you have problems with SMTP authentication
    ),
    'i18n' => array(
        'choice' => array(
            'en-US' => 'English',
            'de-DE' => 'Deutsch'

            // More possible languages:
            // 'fr-FR' => 'Français',
            // 'hu-HU' => 'Magyar',
        ),

        'currency' => 'AUD',

        // The language is usually detected from the user's web browser.
        // If it cannot be detected automatically and there is no cookie from a manual language selection,
        // the following locale will be used as the default "fallback":
        'locale' => 'en-US',
    ),
    'payum' => array(
        'token_storage' => new \Payum\Core\Storage\FilesystemStorage(
            __DIR__.'/../../data/payum',
            'Application\Model\PaymentSecurityToken',
            'hash'
        ),
        'gateways' => array(
            'paypal_ec' => (new \Payum\Paypal\ExpressCheckout\Nvp\PaypalExpressCheckoutGatewayFactory())->create(array(
                'username' => '?',
                'password' => '?',
                'signature' => '?',
                'sandbox' => false
            )),
            'stripe' => (new \Payum\Stripe\StripeCheckoutGatewayFactory())->create(array(
                'publishable_key' => 'pk_test_51N3B1LFxrkun3mFr4l4jqlCMCVaQQBOEaSs389IHa6nNzoUa4S8xekbvdnrM95bxPf9305v2HSGXf4jSUItSTdSj004fHTTvr9',
                'secret_key' => 'sk_test_51N3B1LFxrkun3mFrYa9JoEAMFrlYBLNULmlJzJBNRtY6l76csZP4yhmO2IoXmJYeCgRDUuUUcOUcTrUl9tzzFmwK00t9W7IY8x',
                'sca_flow' => true,
                'payum.template.obtain_token' => '@PayumStripe/Action/stripe_js.html.twig',
                'payum.template.require_confirmation' => '@PayumStripe/Action/stripe_confirm.html.twig'
            )),
            'klarna_checkout' => (new \Payum\Klarna\Checkout\KlarnaCheckoutGatewayFactory())->create(array(
                'secret' => '?',
                'merchant_id' => '?',
                'sandbox' => false
            )),
        ),
        'storages' => array(
            $detailsClass => new \Payum\Core\Storage\FilesystemStorage(__DIR__.'/../../data/payum', $detailsClass, 'id'),
        )
    ),
));
