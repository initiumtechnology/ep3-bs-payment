<?php

namespace Square\Factory;

class Cart
{
    private static $instance = null;
    private $items = [];

    private function __construct()
    {
        // Start the session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Check if the cart exists in the session
        if (!isset($_SESSION['cart'])) {
            // If the cart doesn't exist, create an empty array to represent the cart
            $_SESSION['cart'] = [];
        }

        // Load cart items from the session into the Cart object
        $this->items = $_SESSION['cart'];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Cart();
        }
        return self::$instance;
    }

    public function addToCart($item)
    {
        $this->items[] = $item;
        // Update the session with the latest cart items
        $_SESSION['cart'] = $this->items;
    }

    public function getItems()
    {
        print_r($this->items);

        // // Dummy array of cart items for testing
        // $cartItems = [
        //     [
        //         'id' => 1,
        //         'name' => 'Item 1',
        //         'price' => 10.99,
        //         'quantity' => 2,
        //     ],
        //     [
        //         'id' => 2,
        //         'name' => 'Item 2',
        //         'price' => 5.99,
        //         'quantity' => 3,
        //     ],
        //     // Add more items as needed
        // ];
        // $this->items = $cartItems;

        return $this->items;
    }
}
