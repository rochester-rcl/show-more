<?php
return [
    'controllers' => [
        'invokables' => [
            'ShowMore\Controller\SiteAdmin\Index' => ShowMore\Controller\SiteAdmin\IndexController::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'navigation' => [
        'site' => [
            [
                'label' => 'Show More', // @translate
                'route' => 'admin/site/slug/show-more',
                'controller' => 'index',
                'action' => 'index',
                'useRouteMatch' => true,
                'resource' => 'ShowMore\Controller\SiteAdmin\Index',
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'site' => [
                        'child_routes' => [
                            'slug' => [
                                'child_routes' => [
                                    'show-more' => [
                                        'type' => \Laminas\Router\Http\Segment::class,
                                        'options' => [
                                            'route' => '/show-more[/:action]',
                                            'constraints' => [
                                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                            ],
                                            'defaults' => [
                                                '__NAMESPACE__' => 'ShowMore\Controller\SiteAdmin',
                                                'controller' => 'index',
                                                'action' => 'index',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];