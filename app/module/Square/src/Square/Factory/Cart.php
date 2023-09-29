<?php

namespace Square\Factory;

class Cart
{
    private static $instance = null;
    private $items = [];

    private function __construct()
    {
        // Private constructor to prevent direct instantiation
        // Initialize cartItems from the cookies
        if (isset($_COOKIE['cartItems'])) {
            $cartItemsJson = $_COOKIE['cartItems'];
            $this->items = json_decode($cartItemsJson, true);
        } else {
            $this->items = [];
        }
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
        // Save cartItems to the cookies
        $cartItemsJson = json_encode($this->items);
        // Set the cartItems cookie with a 30-day expiration time (you can adjust the time as needed)
        setcookie('cartItems', $cartItemsJson, time() + (30 * 24 * 60 * 60), '/');
    }

    public function getItems()
    {
        return $this->items;
    }

    public function removeFromCart($index)
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            // Save updated cartItems to the cookies
            $cartItemsJson = json_encode($this->items);
            setcookie('cartItems', $cartItemsJson, time() + (30 * 24 * 60 * 60), '/');
        }
    }

    public function setItems($items)
    {
        // Update the internal items array
        $this->items = $items;

        // Serialize and save cartItems to the cookies
        $cartItemsJson = json_encode($this->items);
        setcookie('cartItems', $cartItemsJson, time() + (30 * 24 * 60 * 60), '/');
    }

}