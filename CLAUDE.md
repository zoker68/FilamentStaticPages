# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

`zoker/filament-static-pages` (`Zoker\FilamentStaticPages\`) — a Filament 5 plugin for static pages with block-based content and page hierarchy, multisite-aware. Developed inside a host workbench app where it is symlinked into `vendor/` as a Composer `path` repo and shares the **root `vendor/`**. Standalone, independently publishable. **Depends on `zoker/filament-multisite`** (pages/content use `HasMultisite`); `zoker/shop` depends on this package.

## Commands

From the host root, via Sail. This package has **no `phpunit.xml`** yet — point the runner at the host config and the package tests dir:

```bash
./vendor/bin/sail php vendor/bin/phpunit -c phpunit.xml packages/zoker/FilamentStaticPages/tests
./vendor/bin/sail php vendor/bin/phpstan analyse -c packages/zoker/FilamentStaticPages/phpstan.neon
./vendor/bin/sail pint packages/zoker/FilamentStaticPages
```

Tests (`tests/Feature`, `tests/Unit`) use Orchestra Testbench + Pest 4. (Adding a package-local `phpunit.xml` would let `-c` target it directly.)

## Architecture

One provider: **`FilamentStaticPagesServiceProvider`** (Spatie `PackageServiceProvider`) — registers 5 migrations (`pages` + `parent_id`/`site_id`, `menu`, `content`), views, translations, config; Blade components under the `fsp::` namespace plus directives `@fspContent` and `@fspMenu`. `StaticPages.php` is the Filament plugin entry.

`src/` highlights:
- **`Models/`** `Page` (uses `HasMultisite`, hierarchy via `parent_id`), `Menu`, `Content`.
- **`Filament/Resources/`** `PageResource`, `ContentResource`, `MenuResource` (full CRUD pages).
- **`Filament/Actions/`** transfer/import-export between sites: `PageTransferAction`, `ContentTransferAction`, abstract `AbstractTransferAction` / `AbstractImportExportAction`.
- **`Classes/`** `BlocksComponentRegistry` (block registry), `BlockComponent`, `Layout`, `FilamentUrlSchema`.
- **`Services/`** `BlocksExportImportService`.
- **`View/Components/`** ~11 `*Block` components (Banner, Slider, QuestionAnswer, ImageWithText, Partners, Heading, Meta, Breadcrumbs, Content) + `Menu`, `RenderContentDirective`.
- **`Http/Controllers/PageController`** renders pages by URL; `Enums/ContentType`; `Observers/PageObserver`.

## Key convention

Page content is **block-based**: blocks are registered in `BlocksComponentRegistry` and rendered through the `*Block` Blade components / `@fspContent` directive. To add a content block, register it and add its component. Pages/content are per-site via `HasMultisite`, and can be transferred across sites with the transfer actions.

## AI (translation & SEO)

SEO generation (Meta block) and translation of copied page blocks use the first‑party `laravel/ai` SDK. Pieces: agents in `src/Ai/Agents/` (`TranslateAgent`, `SeoAgent`); `Services/Translator` (SDK adapter); `Services/BlockContentTranslator` walks blocks using each block's static **`$translatable`** dot‑paths (`*` = every repeater item) — structure/non‑text fields are never touched; `Jobs/TranslatePageBlocksJob` queues the work (translates only the copied tail, append‑safe).

Gated by `config('fsp.ai.enabled')`; provider/model from `fsp.ai.provider|model`; `fsp.ai.base_locale` is the single main language content is only translated *out* of. The **API key is `laravel/ai`'s own** (`OPENAI_API_KEY`, …) — never set in this package. When `zoker/shop` is installed it overrides `fsp.ai.*` from `shop.ai.*` at boot (single config source). To make a new block's text translatable, add `public static array $translatable = [...]` to it.

Changes here are real package changes — they must land in this package's upstream repo.