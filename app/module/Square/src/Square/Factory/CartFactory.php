<?php

namespace Square\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class CartFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sm)
    {
        return new Cart($sm->get('Zend\Db\Adapter\Adapter'));
    }
}