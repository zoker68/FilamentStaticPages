<?php

use Zoker\FilamentMultisite\Http\Middleware\MultisiteMiddleware;
use Zoker\Shop\Http\Middleware\MaintenanceModeMiddleware;

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
        MultisiteMiddleware::class,
        class_exists(MaintenanceModeMiddleware::class) ? MaintenanceModeMiddleware::class : null, // TODO: Delete when Shop is installed
    ],

    'disk' => env('FSP_DISK', 'public'),

    /**
     * AI features (block translation on copy, SEO generation in the Meta block).
     * Backed by the laravel/ai SDK; the provider/model are resolved from here so
     * the host app can switch provider without touching package code.
     */
    'ai' => [
        // Master switch for all AI features in this package.
        'enabled' => env('FSP_AI_ENABLED', false),

        // laravel/ai provider (openai, anthropic, gemini, ...) and model.
        'provider' => env('FSP_AI_PROVIDER', 'openai'),
        'model' => env('FSP_AI_MODEL', 'gpt-4o-mini'),

        // Short description of the site's topic/domain, injected into AI prompts so
        // translations pick the correct domain meaning of ambiguous terms.
        'context' => env('FSP_AI_CONTEXT'),

        // The single main language. Content is only ever translated OUT of it.
        'base_locale' => env('FSP_AI_BASE_LOCALE', 'en'),
    ],
];
