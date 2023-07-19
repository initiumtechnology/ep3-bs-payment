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

        $cartService = Cart::getInstance();

        $cartService->addToCart([
            'id' => 123,
            'name' => 'Item 3',
            'price' => 11.99,
            'quantity' => 1,
        ]);

        $cartItems = $cartService->getItems();

        // Uncomment these lines for debugging
        // print_r("CartController\n");
        // print_r($cartItems);

        $viewModel = new ViewModel([
            'cartItems' => $cartItems,
        ]);

        // Set the view template
        $viewModel->setTemplate('user/account/cart');

        return $viewModel;
    }
}
