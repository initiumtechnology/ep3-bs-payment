<?php
namespace Payum\Stripe\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;
use Payum\Stripe\Keys;
use Payum\Stripe\Request\Api\ObtainTokenForStrongCustomerAuthentication;

/**
 * @param Keys $keys
 * @param Keys $api
 */
class ObtainTokenForStrongCustomerAuthenticationAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use ApiAwareTrait {
        setApi as _setApi;
    }
    use GatewayAwareTrait;

    /**
     * @var string
     */
    protected $templateName;

    /**
     * @deprecated BC will be removed in 2.x. Use $this->api
     *
     * @var Keys
     */
    protected $keys;

    /**
     * @param string $templateName
     */
    public function __construct($templateName)
    {
        $this->templateName = $templateName;

        $this->apiClass = Keys::class;
    }

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        $this->_setApi($api);

        // BC. will be removed in 2.x
        $this->keys = $this->api;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        // syslog(LOG_EMERG,"ObtainTokenForStrongCustomerAuthenticationAction");
        
        /** @var $request ObtainTokenForStrongCustomerAuthentication */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($model['payment_method']) {
            throw new LogicException('The token has already been set.');
        }

        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);
        if ($getHttpRequest->method == 'POST' && isset($getHttpRequest->request['stripeToken'])) {
            $model['payment_method'] = $getHttpRequest->request['stripeToken'];
            $stripePaymentMethod = $getHttpRequest->request['stripePaymentMethod'];
            if ($stripePaymentMethod === 'ideal' || $stripePaymentMethod === 'giropay' || $stripePaymentMethod === 'sofort' ) {
               $model["return_url"] = $request->getToken() ? $request->getToken()->getAfterUrl() : null;
            }
            if ($stripePaymentMethod === 'sepa_debit') {
               $model["mandate_data"] = array( 'customer_acceptance' => array(
                                                      'type' => 'online',
                                                      'online' => array(
                                                          'ip_address' => $_SERVER['REMOTE_ADDR'],
                                                          'user_agent' => $_SERVER['HTTP_USER_AGENT']
                                                      )
                                                  ));
            }
            $metadata = $model['metadata'];
            $metadata['stripePaymentMethod'] = $stripePaymentMethod;
            $model['metadata'] = $metadata;

            return;
        }

        $this->gateway->execute($renderTemplate = new RenderTemplate($this->templateName, array(
            'model' => $model,
            'publishable_key' => $this->keys->getPublishableKey(),
            'actionUrl' => $request->getToken() ? $request->getToken()->getTargetUrl() : null,
        )));

        throw new HttpResponse($renderTemplate->getResult());
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof ObtainTokenForStrongCustomerAuthentication &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
