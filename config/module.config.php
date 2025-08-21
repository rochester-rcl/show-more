<?php
return [
    'controllers' => [
        'invokables' => [
            'ShowMore\Controller\SiteAdmin\Index' => 'ShowMore\Controller\SiteAdmin\IndexController',
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
                                        'type' => 'Literal',
                                        'options' => [
                                            'route' => '/show-more',
                                            'defaults' => [
                                                '__NAMESPACE__' => 'ShowMore\Controller\SiteAdmin',
                                                'controller' => 'Index',
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
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'showMore' => 'ShowMore\View\Helper\ShowMore',
        ],
    ],
];