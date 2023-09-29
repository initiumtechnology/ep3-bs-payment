<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use \Square\Factory\Cart;
use Exception;
use RuntimeException;

use Zend\Session\Container;

class CartController extends AbstractActionController
{
    public function getAction()
    {
        // Get current cart items stored in cookies
        $cartService = Cart::getInstance();
        $cartItems = $cartService->getItems();

        // Check user info
        $userSessionManager = $this->getServiceLocator()->get('User\Manager\UserSessionManager');
        $user = $userSessionManager->getSessionUser();

        // Check if the user is a member
        $member = 0;
        if ($user != null && $user->getMeta('member') != null) {
           $member = $user->getMeta('member');
        }

        // Get the manager instances
        $squareManager = $this->getServiceLocator()->get('Square\Manager\SquareManager');
        $squarePricingManager = $this->getServiceLocator()->get('Square\Manager\SquarePricingManager');
        $squareValidator = $this->getServiceLocator()->get('Square\Service\SquareValidator');

        // Create an array to store items that are still available
        $updatedCartItems = [];

        foreach ($cartItems as &$cartItem) {
            // Check if the square is still available
            $is_bookable = false;
            try {
                // syslog(LOG_EMERG, json_encode($cartItems));
                $byproducts = $squareValidator->isBookable($cartItem['dateStart'], $cartItem['dateEnd'], $cartItem['timeStart'], $cartItem['timeEnd'], $cartItem['square']);
                $is_bookable = $byproducts['bookable'];
            } catch (RuntimeException $e) {
                $is_bookable = false;
            }

            // If the booking is no longer available
            if (! $is_bookable) {
                $this->flashMessenger()->addErrorMessage(sprintf($this->t('%sA booking is no longer available!%s'),
                        '<b>', '</b>'));
            } else {
                // Get square name and price
                $square = $squareManager->get($cartItem['square']);
                $finalPrice = $squarePricingManager->getFinalPricingInRange($byproducts['dateStart'], $byproducts['dateEnd'], $byproducts['square'], 1, $member);

                // Store name and price
                $cartItem['squareName'] = $square->get('name');
                $cartItem['price'] = $finalPrice['price'];

                // Add to updated cart
                $updatedCartItems[] = $cartItem;
            }
        }

        // Update the cart with the available items
        $cartService->setItems($updatedCartItems);

        // Return to view
        $viewModel = new ViewModel([
            'cartItems' => $updatedCartItems,
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