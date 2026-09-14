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

- **Block translation** — when copying a page to another site (Page transfer action), tick *Translate content* to translate the copied text blocks into the target site's language (queued). Translation is offered/queued **only when copying out of the default site** (`Site::is_default`) into a site with a different locale — the default site is the single content source. (Direction is keyed on the *site*, not the locale, because a locale may repeat across sites.)
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
| `FSP_AI_MODEL` | `gpt-5.6-luna` | Model. |
| `FSP_AI_CONTEXT` | — | Short description of the site's topic/domain, injected into prompts so ambiguous terms are translated in the right sense. |

The API key is read by `laravel/ai` from its own config/env (`OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, …) — not from this package's config.

Translation runs on the queue (`php artisan queue:work`). After install/update run `php artisan package:discover` so `Laravel\Ai\AiServiceProvider` is registered.

> **With `zoker/shop` installed**, shop is the single source of AI config: it overrides `fsp.ai.*` from its own `shop.ai.*` at boot, so set the `AI_*` variables in shop and the `FSP_AI_*` ones are ignored.

# Cross-site link rewriting (Page transfer)

When a page is copied to another site (Page transfer action), internal links inside its blocks are rewritten for the target site: they get the target site's locale prefix (e.g. `/contact` → `/ru/contact`), and an absolute URL when the target site has its own `domain`. External links, `mailto:`/`tel:`, in-page anchors (`#…`) and asset paths (`/storage/…`) are left untouched. This runs **synchronously on every cross-site copy**, independent of the *Translate content* toggle. Disable with `FSP_TRANSFER_REWRITE_LINKS=false` (or `fsp.transfer.rewrite_links`).

Which fields hold links is declared per block via static properties — same dot-path syntax as `$translatable` (`*` matches every repeater item):

| Property | What it does | Example |
|---|---|---|
| `$links` | plain URL fields → host + locale-prefix rewrite | `['link', 'slides.*.link', 'blocks.*.link.url']` |
| `$htmlLinks` | rich-text (HTML) fields → rewrite the `href="…"` attributes inside | `['content', 'slides.*.text']` |

```php
class BannerBlock extends \Zoker\FilamentStaticPages\Classes\BlockComponent
{
    public static array $links = ['link'];
    // public static array $htmlLinks = ['content'];   // rich-text with inline <a href>
}
```

A block that declares neither is left untouched (e.g. the Breadcrumbs block stores links as runtime-resolved references, so it needs no declaration). Non-page path segments can be excluded globally via `fsp.transfer.skip_path_prefixes` (default `['storage']`).

Canonical URLs are **not** rewritten here — they are an SEO/render-time concern handled by the multisite `AlternateLinks` service, not stored per copy.

> Blocks registered from **other packages** (via `BlocksComponentRegistry::register(...)`) participate automatically — just extend `BlockComponent` (or declare the same public static arrays) and list their link/text fields. `zoker/shop`'s built-in blocks already do.
