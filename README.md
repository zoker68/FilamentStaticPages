# FilamentStaticPages
Simple plugin for static pages

# Install

```bash
composer require zoker/filament-static-pages
```

## Publish config

```bash
php artisan vendor:publish --tag=fsp-config
```

## Publish views

```bash
php artisan vendor:publish --tag=fsp-views
```

## Publish migrations

```bash
php artisan vendor:publish --tag=fsp-migrations
```

## Migrations

```bash
php artisan migrate
```

## Add to Filament Service Provider
```php
->plugin(StaticPages::make())
```

# New Component:

- In directory `app/View/Components` create new component
```php
namespace App\View\Components;

class TextBlock extends \Zoker\FilamentStaticPages\Classes\BlockComponent
{
    public static string $viewTemplate = 'components.text'; 
    
    public static function getSchema()
    {
        return [
            Textarea::make('data.content'),
        ];
    }
}

```

- You can add label to component (optional)
```php
    public static string $label = 'Text Block';
```

- Set view for component
```php
    public static string $viewTemplate = 'components.text';
```

- Register component in ServiceProvider

```php
\Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry::register(\App\View\Components\TextBlock::class, 'TextBlock');
```

# Menu

```bladehtml
@fspMenu('menu-code')
```

# Content everywhere

```bladehtml
@fspContent('content-code')
```

## With context

You can pass additional context to blocks:

```bladehtml
@fspContent('content-code', ['product' => $product])
```

Access context in block component:

```bladehtml
{{ $context['product']->name }}
```

# AI features (translation & SEO)

Powered by the first‑party [`laravel/ai`](https://laravel.com/ai) SDK, **off by default**.

- **Block translation** — when copying a page to another site (Page transfer action), tick *Translate content* to translate the copied text blocks into the target site's language (queued). Translation only flows **out of the main language** (`base_locale`).
- **SEO generation** — the Meta block's *Generate SEO with AI* button.

Which fields of a block hold translatable text is declared per block via the static `$translatable` property (dot paths; `*` matches every repeater item):

```php
public static array $translatable = ['heading'];
// nested example:
public static array $translatable = ['categories.*.questions.*.answer'];
```

Configure via `.env` (standalone — i.e. without `zoker/shop`):

| Variable | Default | Purpose |
|---|---|---|
| `FSP_AI_ENABLED` | `false` | Master switch. |
| `FSP_AI_PROVIDER` | `openai` | `laravel/ai` provider. |
| `FSP_AI_MODEL` | `gpt-4o-mini` | Model. |
| `FSP_AI_CONTEXT` | — | Short description of the site's topic/domain, injected into prompts so ambiguous terms are translated in the right sense. |
| `FSP_AI_BASE_LOCALE` | `en` | The single main language; content is only translated *out* of it. |

The API key is read by `laravel/ai` from its own config/env (`OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, …) — not from this package's config.

Translation runs on the queue (`php artisan queue:work`). After install/update run `php artisan package:discover` so `Laravel\Ai\AiServiceProvider` is registered.

> **With `zoker/shop` installed**, shop is the single source of AI config: it overrides `fsp.ai.*` from its own `shop.ai.*` at boot, so set the `AI_*` variables in shop and the `FSP_AI_*` ones are ignored.
