<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use \Square\Factory\Cart;

use Zend\Session\Container;

class CartController extends AbstractActionController
{
    public function getAction()
    {
        // Get current cart items stored in cookies
        $cartService = Cart::getInstance();
        $cartItems = $cartService->getItems();

        // Return to view
        $viewModel = new ViewModel([
            'cartItems' => $cartItems,
        ]);

        // Set the view template
        $viewModel->setTemplate('user/account/cart');

        return $viewModel;
    }
}
