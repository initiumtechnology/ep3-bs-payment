<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class CartController extends AbstractActionController
{
    public function getAction()
    {
        // Dummy array of cart items for testing
        $cartItems = [
            [
                'id' => 1,
                'name' => 'Item 1',
                'price' => 10.99,
                'quantity' => 2,
            ],
            [
                'id' => 2,
                'name' => 'Item 2',
                'price' => 5.99,
                'quantity' => 3,
            ],
            // Add more items as needed
        ];

        $viewModel = new ViewModel([
            'cartItems' => $cartItems,
        ]);

        // Set the view template
        $viewModel->setTemplate('user/account/cart');

        return $viewModel;
    }
}
