<?php

namespace Square\Factory;

class Cart
{
    private static $instance;
    private $items = [];

    private function __construct()
    {
        // Private constructor to prevent direct instantiation
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
    }

    public function getItems()
    {
        // Dummy array of cart items for testing
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
