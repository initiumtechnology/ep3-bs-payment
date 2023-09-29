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
            $cellLabel = $view->t('Cart');
            $style = 'cc-cart';
            return $view->calendarCellLink($cellLabel, $view->url('user/cart'), $style);
        }
    }
}