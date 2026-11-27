# Changelog

## 2.10.0 — 2026-11-27

### Upgrading

- **A host with its own copy of `fsp::components.meta-data`** adds `@if($structuredData)<script type="application/ld+json">{!! $structuredData !!}</script>@endif` inside its `@push('meta')`, otherwise the home page gets no JSON-LD.

### Added

- **JSON-LD Organization and WebSite on the home page.** The Meta block of the page with an empty url (the one the `index` route serves) adds one `<script type="application/ld+json">` to its `meta` push, next to the title and Open Graph tags. Search engines and AI search read the site name, logo, social profiles, contacts and return policy from it.
  - `Organization` (type from the config, e.g. `OnlineStore`): `@id` is `<home url>#organization`, `name` is `fsp.site_name`, `url` is the home page of the current site with its prefix. `logo`, `legalName`, `alternateName`, `sameAs`, `email`, `telephone`, `vatID` and a `PostalAddress` are added when set.
  - `hasMerchantReturnPolicy` (`MerchantReturnPolicy`, finite return window) when `return_policy` is set with days and countries.
  - `WebSite` with `name`, `alternateName`, `url` and `publisher` pointing at the organization. There is no `SearchAction`: Google retired the sitelinks search box in 2024.
  - New config `fsp.structured_data`: `enabled` (`FSP_STRUCTURED_DATA_ENABLED`, on by default), `organization`, `return_policy`. Empty values are left out of the markup. No tag on other pages, with `enabled = false`, or with an empty `fsp.site_name`.
  - `MetaBlock::isHomePage()`.

## 2.9.0 — 2026-11-23

### Upgrading

- **`laravel/ai` ^1.4 (was ^0.8).** Update with `composer update zoker/filament-static-pages laravel/ai -W`. `zoker/shop` 3.49.0 needs this release.
  - `laravel/ai` 1.0 conflicts with `laravel/mcp` below 1.0, so a host with `laravel/boost` needs boost 2.10 or later.
  - The AWS SDK is no longer installed with `laravel/ai`; only the Bedrock provider needs it (`composer require aws/aws-sdk-php`).
- **The default AI model is `gpt-6-luna`** (was `gpt-4o-mini`). Set `FSP_AI_MODEL` to keep the old one. With `zoker/shop` installed, `fsp.ai.*` comes from `shop.ai.*` as before.

### Added

- `fsp::lang.messages.ai_insufficient_credits` (en, ru, sl).

### Changed

- **An exhausted AI balance on the Meta block's "Generate SEO" button shows a notification instead of a server error.** Since `laravel/ai` 1.4, OpenAI billing and quota errors throw `InsufficientCreditsException`; the button now catches it and reports the exception to the log. `TranslatePageBlocksJob` already logs any AI failure without retrying.

## 2.8.3 — 2026-09-10

### Fixed

- **An empty list of page routes is no longer cached.**
  - Before, an artisan run against another, empty database that shares the cache store cached an empty list for the live site, and its pages stayed unresolved until the cache was cleared.
  - Page routes and allowed URLs are now cached only when not empty.
  - An empty entry left by an older version is treated as a miss.
- **The `add_site_id_to_pages_table` migration ignores global scopes** when it assigns the first site to existing pages.

## 2.8.2 — 2026-09-09

### Changed

- **The Meta block title gets the ` | site` suffix again,** now from `fsp.site_name`. The suffix is added both to the default title (the page name) and to the AI-generated one. The AI is told the suffix is appended automatically.

## 2.8.1 — 2026-09-08

### Changed

- **The AI prompts name the site by `fsp.site_name` instead of `app.name`.**
- **The Meta block no longer appends ` | app.name` to the title.** It came back in 2.8.2, with `fsp.site_name`.

## 2.8 — 2026-09-06

### Added

- **Open Graph and Twitter card tags in the Meta block:**
  - `og:type`, `og:title`, `og:description`, `og:url` (the page canonical URL), `og:image`, `og:site_name`, `og:locale`;
  - `twitter:card` = `summary_large_image`.
- **A "Social share image" upload in the Meta block:**
  - 1200×630 recommended, stored on `fsp.disk` under `meta/`;
  - without one, `fsp.og_image_url` (`FSP_OG_IMAGE_URL`) is used.
- **New config keys:**
  - `fsp.site_name` (`FSP_SITE_NAME`, defaults to `APP_NAME`);
  - `fsp.og_image_url`;
  - `fsp.og_locales`, which maps the app locale to the `language_TERRITORY` form `og:locale` needs.
