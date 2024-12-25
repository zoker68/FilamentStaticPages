<?php

/**
 * Configuration file for the Filament Static Pages package.
 */
return [
    /**
     * The prefix used for all static page routes.
     */
    'route_prefix' => '',

    /**
     * The default layout to use for static pages.
     */
    'layout' => 'app',

    /**
     * The prefix for all database table names.
     */
    'table_prefix' => 'zoker_fsp_',

    /**
     * The middleware to apply to all static page routes.
     */
    'middlewares' => [
        'web',
        class_exists(\Zoker\Shop\Http\Middleware\MaintenanceModeMiddleware::class) ? \Zoker\Shop\Http\Middleware\MaintenanceModeMiddleware::class : null, //TODO: Delete when Shop is installed
    ],

    'disk' => env('FSP_DISK', 'public'),
];
