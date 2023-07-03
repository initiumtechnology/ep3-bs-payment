<?php

return [
    'router' => [
        'routes' => [
            'square' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/square',
                    'defaults' => [
                        'controller' => 'Square\Controller\Square',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'booking' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/booking',
                            'defaults' => [
                                'controller' => 'Square\Controller\Booking',
                            ],
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'customization' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/customization',
                                    'defaults' => [
                                        'action' => 'customization',
                                    ],
                                ],
                            ],
                            'addtocart' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/addtocart',
                                    'defaults' => [
                                        'action' => 'addtocart',
                                    ],
                                ],
                            ],
                            'confirmation' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/confirmation',
                                    'defaults' => [
                                        'action' => 'confirmation',
                                    ],
                                ],
                            ],
                            'cancellation' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/cancellation',
                                    'defaults' => [
                                        'action' => 'cancellation',
                                    ],
                                ],
                            ],
                            'payment_done' => [
                                'type' => 'segment',
                                'options' => [
                                    'route'    => '/payment/done[/:payum_token]',
                                    'defaults' => [
                                        'action' => 'done',
                                    ],
                                ],
                            ],
                            'payment_confirm' => [
                                'type' => 'segment',
                                'options' => [
                                    'route'    => '/payment/confirm[/:payum_token]',
                                    'defaults' => [
                                        'action' => 'confirm',
                                    ],
                                ],
                            ],
                            'payment_webhook' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route'    => '/payment/webhook',
                                    'defaults' => [
                                        'action' => 'webhook',
                                    ],
                                ],
                            ],
                            'my-booking' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/my-booking',
                                    'defaults' => [
                                        'controller' => 'Square\Controller\Booking',
                                        'action' => 'myBooking',
                                    ],
                                ],
                            ],
                            'new-booking' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/new-booking',
                                    'defaults' => [
                                        'controller' => 'Square\Controller\Booking',
                                        'action' => 'newBooking',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Square\Controller\Square' => 'Square\Controller\SquareController',
            'Square\Controller\Booking' => 'Square\Controller\BookingController',
        ],
    ],

    'service_manager' => array(
        'factories' => array(
            'Square\Manager\SquareManager' => 'Square\Manager\SquareManagerFactory',
            'Square\Manager\SquarePricingManager' => 'Square\Manager\SquarePricingManagerFactory',
            'Square\Manager\SquareProductManager' => 'Square\Manager\SquareProductManagerFactory',

            'Square\Service\SquareValidator' => 'Square\Service\SquareValidatorFactory',

            'Square\Table\SquareMetaTable' => 'Square\Table\SquareMetaTableFactory',
            'Square\Table\SquareTable' => 'Square\Table\SquareTableFactory',

            'Square\Table\SquarePricingTable' => 'Square\Table\SquarePricingTableFactory',
            'Square\Table\SquareProductTable' => 'Square\Table\SquareProductTableFactory',
            'Square\Factory\Cart' => 'Square\Factory\CartFactory'
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'SquareCapacityInfo' => 'Square\View\Helper\CapacityInfo',
            'SquareDateFormat' => 'Square\View\Helper\DateFormat',
        ),

        'factories' => array(
            'SquarePricingHints' => 'Square\View\Helper\PricingHintsFactory',
            'SquarePricingSummary' => 'Square\View\Helper\PricingSummaryFactory',

            'SquareProductChoice' => 'Square\View\Helper\ProductChoiceFactory',
            'SquareQuantityChoice' => 'Square\View\Helper\QuantityChoiceFactory',
            'SquareTimeBlockChoice' => 'Square\View\Helper\TimeBlockChoiceFactory',
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
];
