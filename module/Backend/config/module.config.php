<?php

return array(
    'router' => array(
        'routes' => array(
            'backend' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/backend',
                    'defaults' => array(
                        'controller' => 'Backend\Controller\Index',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'user' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/user',
                            'defaults' => array(
                                'controller' => 'Backend\Controller\User',
                                'action' => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'edit' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/edit[/:uid]',
                                    'defaults' => array(
                                        'action' => 'edit',
                                    ),
                                    'constraints' => array(
                                        'uid' => '[0-9]+',
                                    ),
                                ),
                            ),
                            'delete' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/delete/:uid',
                                    'defaults' => array(
                                        'action' => 'delete',
                                    ),
                                    'constraints' => array(
                                        'uid' => '[0-9]+',
                                    ),
                                ),
                            ),
                            'interpret' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/interpret',
                                    'defaults' => array(
                                        'action' => 'interpret',
                                    ),
                                ),
                            ),
                            'stats' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/stats',
                                    'defaults' => array(
                                        'action' => 'stats',
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'booking' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/booking',
                            'defaults' => array(
                                'controller' => 'Backend\Controller\Booking',
                                'action' => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'edit' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/edit',
                                    'defaults' => array(
                                        'action' => 'edit',
                                    ),
                                ),
                                'may_terminate' => true,
                                'child_routes' => array(
                                    'range' => array(
                                        'type' => 'Segment',
                                        'options' => array(
                                            'route' => '/range/:bid',
                                            'defaults' => array(
                                                'action' => 'editRange',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'delete' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/delete/:rid',
                                    'defaults' => array(
                                        'action' => 'delete',
                                    ),
                                    'constraints' => array(
                                        'rid' => '[0-9]+',
                                    ),
                                ),
                            ),
                            'stats' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/stats',
                                    'defaults' => array(
                                        'action' => 'stats',
                                    ),
                                ),
                            ),
                            'bills' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/bills/:bid',
                                    'defaults' => array(
                                        'action' => 'bills',
                                    ),
                                    'constraints' => array(
                                        'bid' => '[0-9]+',
                                    ),
                                ),
                            ),
                            'players' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/players/:bid',
                                    'defaults' => array(
                                        'action' => 'players',
                                    ),
                                    'constraints' => array(
                                        'bid' => '[0-9]+',
                                    ),
                                ),
                            ),
                            'webhook' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/webhook',
                                    'defaults' => array(
                                        'action' => 'webhook',
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'squarecontrol' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/squarecontrol',
                            'defaults' => array(
                                'controller' => 'Backend\Controller\SquareControl',
                                'action' => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'removeinactivedoorcodes' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/removeinactivedoorcodes',
                                    'defaults' => array(
                                        'action' => 'removeinactivedoorcodes',
                                    ),
                                ),
                            ),
                            'deletedoorcode' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/deletedoorcode/:dcid',
                                    'defaults' => array(
                                        'action' => 'deletedoorcode',
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'event' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/event',
                            'defaults' => array(
                                'controller' => 'Backend\Controller\Event',
                                'action' => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'edit' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/edit[/:eid]',
                                    'defaults' => array(
                                        'action' => 'edit',
                                    ),
                                    'constraints' => array(
                                        'eid' => '[0-9]+',
                                    ),
                                ),
                            ),
                            'edit-choice' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/edit-choice',
                                    'defaults' => array(
                                        'action' => 'editChoice',
                                    ),
                                ),
                            ),
                            'delete' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/delete/:eid',
                                    'defaults' => array(
                                        'action' => 'delete',
                                    ),
                                    'constraints' => array(
                                        'eid' => '[0-9]+',
                                    ),
                                ),
                            ),
                            'stats' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/stats',
                                    'defaults' => array(
                                        'action' => 'stats',
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'config' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/config',
                            'defaults' => array(
                                'controller' => 'Backend\Controller\Config',
                                'action' => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes'  => array(
                            'text' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/text',
                                    'defaults' => array(
                                        'action' => 'text',
                                    ),
                                ),
                            ),
                            'info' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/info',
                                    'defaults' => array(
                                        'action' => 'info',
                                    ),
                                ),
                            ),
                            'help' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/help',
                                    'defaults' => array(
                                        'action' => 'help',
                                    ),
                                ),
                            ),
                            'term' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/term',
                                    'defaults' => array(
                                        'action' => 'term',
                                    ),
                                ),
                            ),
                            'cancelpolicy' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/cancelpolicy',
                                    'defaults' => array(
                                        'action' => 'cancelpolicy',
                                    ),
                                ),
                            ),
                            'square' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/square',
                                    'defaults' => array(
                                        'controller' => 'Backend\Controller\ConfigSquare',
                                        'action' => 'index',
                                    ),
                                ),
                                'may_terminate' => true,
                                'child_routes' => array(
                                    'edit' => array(
                                        'type' => 'Segment',
                                        'options' => array(
                                            'route' => '/edit[/:sid]',
                                            'defaults' => array(
                                                'action' => 'edit',
                                            ),
                                            'constraints' => array(
                                                'sid' => '[0-9]+',
                                            ),
                                        ),
                                        'may_terminate' => true,
                                        'child_routes' => array(
                                            'info' => array(
                                                'type' => 'Literal',
                                                'options' => array(
                                                    'route' => '/info',
                                                    'defaults' => array(
                                                        'action' => 'editInfo',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                    'pricing' => array(
                                        'type' => 'Literal',
                                        'options' => array(
                                            'route' => '/pricing',
                                            'defaults' => array(
                                                'action' => 'pricing',
                                            ),
                                        ),
                                    ),
                                    'product' => array(
                                        'type' => 'Literal',
                                        'options' => array(
                                            'route' => '/product',
                                            'defaults' => array(
                                                'action' => 'product',
                                            ),
                                        ),
                                        'may_terminate' => true,
                                        'child_routes' => array(
                                            'edit' => array(
                                                'type' => 'Segment',
                                                'options' => array(
                                                    'route' => '/edit[/:spid]',
                                                    'defaults' => array(
                                                        'action' => 'productEdit',
                                                    ),
                                                    'constraints' => array(
                                                        'spid' => '[0-9]+',
                                                    ),
                                                ),
                                            ),
                                            'delete' => array(
                                                'type' => 'Segment',
                                                'options' => array(
                                                    'route' => '/delete/:spid',
                                                    'defaults' => array(
                                                        'action' => 'productDelete',
                                                    ),
                                                    'constraints' => array(
                                                        'spid' => '[0-9]+',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                    'coupon' => array(
                                        'type' => 'Literal',
                                        'options' => array(
                                            'route' => '/coupon',
                                            'defaults' => array(
                                                'action' => 'coupon',
                                            ),
                                        ),
                                    ),
                                    'delete' => array(
                                        'type' => 'Segment',
                                        'options' => array(
                                            'route' => '/delete/:sid',
                                            'defaults' => array(
                                                'action' => 'delete',
                                            ),
                                            'constraints' => array(
                                                'sid' => '[0-9]+',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'behaviour' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/behaviour',
                                    'defaults' => array(
                                        'action' => 'behaviour',
                                    ),
                                ),
                                'may_terminate' => true,
                                'child_routes' => array(
                                    'rules' => array(
                                        'type' => 'Literal',
                                        'options' => array(
                                            'route' => '/rules',
                                            'defaults' => array(
                                                'action' => 'behaviourRules',
                                            ),
                                        ),
                                    ),
                                    'status-colors' => array(
                                        'type' => 'Literal',
                                        'options' => array(
                                            'route' => '/status-colors',
                                            'defaults' => array(
                                                'action' => 'behaviourStatusColors',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'Backend\Controller\Index' => 'Backend\Controller\IndexController',
            'Backend\Controller\User' => 'Backend\Controller\UserController',
            'Backend\Controller\Booking' => 'Backend\Controller\BookingController',
            'Backend\Controller\SquareControl' => 'Backend\Controller\SquareControlController',
            'Backend\Controller\Event' => 'Backend\Controller\EventController',
            'Backend\Controller\Config' => 'Backend\Controller\ConfigController',
            'Backend\Controller\ConfigSquare' => 'Backend\Controller\ConfigSquareController',
        ),
    ),

    'controller_plugins' => array(
        'invokables' => array(
            'BackendBookingDetermineFilters' => 'Backend\Controller\Plugin\Booking\DetermineFilters',

            'BackendUserDetermineFilters' => 'Backend\Controller\Plugin\User\DetermineFilters',
        ),

        'factories' => array(
            'BackendBookingCreate' => 'Backend\Controller\Plugin\Booking\CreateFactory',
            'BackendBookingDetermineParams' => 'Backend\Controller\Plugin\Booking\DetermineParamsFactory',
            'BackendBookingUpdate' => 'Backend\Controller\Plugin\Booking\UpdateFactory',
        ),
    ),

    'service_manager' => array(
        'factories' => array(
            'Backend\Service\MailService' => 'Backend\Service\MailServiceFactory',
        ),
    ),

    'form_elements' => array(
        'factories' => array(
            'Backend\Form\Booking\EditForm' => 'Backend\Form\Booking\EditFormFactory',

            'Backend\Form\Event\EditForm' => 'Backend\Form\Event\EditFormFactory',

            'Backend\Form\ConfigSquare\EditProductForm' => 'Backend\Form\ConfigSquare\EditProductFormFactory',

            'Backend\Form\User\EditForm' => 'Backend\Form\User\EditFormFactory',
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'BackendBookingsFormat' => 'Backend\View\Helper\Booking\BookingsFormat',

            'BackendEventsFormat' => 'Backend\View\Helper\Event\EventsFormat',

            'BackendSquareProductsFormat' => 'Backend\View\Helper\Square\ProductsFormat',

            'BackendSquareFormat' => 'Backend\View\Helper\Square\SquareFormat',
            'BackendSquaresFormat' => 'Backend\View\Helper\Square\SquaresFormat',

            'BackendDoorCodeFormat' => 'Backend\View\Helper\SquareControl\DoorCodeFormat',
            'BackendDoorCodesFormat' => 'Backend\View\Helper\SquareControl\DoorCodesFormat', 

            'BackendUserFilterHelp' => 'Backend\View\Helper\User\FilterHelp',
            'BackendUserFormat' => 'Backend\View\Helper\User\UserFormat',
            'BackendUsersFormat' => 'Backend\View\Helper\User\UsersFormat',

            'BackendInfo' => 'Backend\View\Helper\Info',
        ),

        'factories' => array(
            'BackendBookingFormat' => 'Backend\View\Helper\Booking\BookingFormatFactory',

            'BackendEventFormat' => 'Backend\View\Helper\Event\EventFormatFactory',

            'BackendSquareProductFormat' => 'Backend\View\Helper\Square\ProductFormatFactory',
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
);
