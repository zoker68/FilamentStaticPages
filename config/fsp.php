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
     * Cross-site transfer behavior.
     */
    'transfer' => [
        // Rewrite internal links in copied blocks to the target site (host + locale prefix).
        // Disable to keep block links exactly as authored on the source site.
        'rewrite_links' => env('FSP_TRANSFER_REWRITE_LINKS', true),

        // Leading path segments that are NOT locale-scoped page routes (e.g. the public
        // storage dir) and must be left untouched by link rewriting.
        'skip_path_prefixes' => ['storage'],
    ],

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

        // HTTP timeout (seconds) for a single AI request. Overridden by shop when
        // installed; declared here so standalone FilamentStaticPages has its own default.
        'timeout' => env('FSP_AI_TIMEOUT', 180),

        // Translator glossary, broken down per target language, plus "do not translate"
        // product/brand names. Steers AI translation & SEO toward the correct terms.
        // Used standalone; when zoker/shop is installed it supplies this from its database.
        // Each row: ['term' => string, 'locale' => ?string, 'translation' => ?string,
        //            'note' => ?string, 'do_not_translate' => bool]. Example:
        //   ['term' => 'Magic Bits', 'do_not_translate' => true],
        //   ['term' => 'bit', 'locale' => 'ru', 'translation' => 'фреза', 'note' => 'nail drill bit'],
        'glossary' => [],
    ],
];
