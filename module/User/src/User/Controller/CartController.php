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

        // TODO: Get price from cart service and add to each item
        foreach ($cartItems as &$cartItem) {
            $cartItem['price'] = 10;
        }

        // Return to view
        $viewModel = new ViewModel([
            'cartItems' => $cartItems,
        ]);

        // Set the view template
        $viewModel->setTemplate('user/account/cart');

        return $viewModel;
    }

    public function removeItemAction()
    {

        $index = $this->params()->fromRoute('index');

        // Get the cart service instance
        $cartService = Cart::getInstance();

        print_r($cartService->getItems());

        // Remove the item from the cart using the index
        $cartService->removeFromCart($index);

        // Redirect back to the cart page with the removed query parameter
        return $this->redirect()->toRoute('user/cart');
    }

}
