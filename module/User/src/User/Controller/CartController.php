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

        // Get the manager instances
        $squareManager = $this->getServiceLocator()->get('Square\Manager\SquareManager');
        $squarePricingManager = $this->getServiceLocator()->get('Square\Manager\SquarePricingManager');
        
        foreach ($cartItems as &$cartItem) {
            print_r($cartItem['square']);
            $square = $squareManager->get($cartItem['square']);
            $dateStart = $this->convertToDateTime($cartItem['dateStart']);
            $dateEnd = $this->convertToDateTime($cartItem['dateEnd']);

            $price = $squarePricingManager->getFinalPricingInRange($dateStart, $dateEnd, $square, 1, 0);
            print_r($price);
            $cartItem['price'] = $price['price']/100;
        }

        // Return to view
        $viewModel = new ViewModel([
            'cartItems' => $cartItems,
        ]);

        // Set the view template
        $viewModel->setTemplate('user/account/cart');

        return $viewModel;
    }

    private function convertToDateTime($value)
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        }
        return $value;
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
