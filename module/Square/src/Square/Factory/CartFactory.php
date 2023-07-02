<?php
namespace Cart\Factory;

use Cart\Factory\Cart;
use Interop\Container\ContainerInterface;

class CartFactory
{
    public function __invoke(ContainerInterface $container)
    {
        // Create an instance of the Cart object
        $cart = new Cart();

        // Retrieve the booking details from the container or any other source
        $bookingDetails = $container->get('Booking\Details');

        // Add the booking details to the cart
        $cart->addBookings($bookingDetails);

        return $cart;
    }
}
