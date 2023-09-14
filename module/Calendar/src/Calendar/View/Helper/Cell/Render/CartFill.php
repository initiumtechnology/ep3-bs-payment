<?php

namespace Calendar\View\Helper\Cell\Render;

use Square\Entity\Square;
use Zend\View\Helper\AbstractHelper;
use \Square\Factory\Cart;

class CartFill extends AbstractHelper
{

    public function __invoke($user, array $cellLinkParams)
    {
        $view = $this->getView();

     if ($user) {
                 $cellLabel = $view->t('CART');
                 //$cellGroup = ' cc-cart';
                 $style = 'cc-cart';
            //     $style = 'cc-own';

            //      if ($userBooking->getMeta('directpay') == 'true' and $userBooking->get('status_billing')!= 'paid') {
            //          $cellLabel = $view->t('Your Booking TRY');
            //          $style = 'cc-try';
            //      }

            //     $cartService = Cart::getInstance();
            //     $cartItems = $cartService->getItems();


                // of the 

                // if ($square->need('sid') == $cartItems[0]['square']) {
                //      $cellLabel = $view->t('In Cart');
                //      $style = 'cc-cart';
                //      syslog(LOG_EMERG, 'cart theme');
                //  }


                //syslog(LOG_EMERG, 'nah mate');
                // syslog(LOG_EMERG, $cartItems[0]['square']);
                // syslog(LOG_EMERG, $square->need('sid'));
                // syslog(LOG_EMERG, print_r($view->url('square', [], $cellLinkParams), true));
                // syslog(LOG_EMERG, print_r($reservations, true));

                //syslog(LOG_EMERG, print_r($userBooking, true));




                // foreach ($cartItems as &$cartItem) {
                //     $cellLabel = $view->t('Your Booking');

                // } 


                return $view->calendarCellLink($cellLabel, $view->url('square', [], $cellLinkParams), $style);

            }
     }}
