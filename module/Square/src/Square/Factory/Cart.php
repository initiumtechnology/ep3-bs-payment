<?php

namespace Square\Factory;

class Cart
{
    private $items = [];

    public function addToCart($item)
    {
        $this->items[] = $item;
    }

    public function getItems()
    {
        return $this->items;
    }
}