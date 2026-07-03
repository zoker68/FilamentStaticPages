<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Services;

use Zoker\FilamentMultisite\Models\Site;

/**
 * Rewrites a single link so it points at the correct target site when a page is
 * copied across sites:
 *  - internal links get the target site's locale prefix (and, when the target
 *    lives on its own domain, an absolute URL);
 *  - external links, in-page anchors and non-http schemes (mailto:, tel:, ...)
 *    are returned untouched.
 *
 * "Internal" means the URL is relative (root-relative) or points at a host we
 * own — config('app.url') or any Site->domain. The source site's / any active
 * site's locale prefix is stripped before the target prefix is applied, so both
 * "/contact" and "/en/contact" become "/ru/contact" on a site with prefix "ru".
 */
class LinkRewriter
{
    /** @var array<int, string>|null */
    private ?array $hosts = null;

    /** @var array<int, string>|null */
    private ?array $prefixes = null;

    public function rewrite(string $url, Site $source, Site $target): string
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '#') || $this->hasNonHttpScheme($url)) {
            return $url;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

        $host = $parts['host'] ?? null;
        $port = $parts['port'] ?? null;

        // Absolute / protocol-relative URL on some other host (or a different port
        // on our host) → external, leave as-is.
        if ($host !== null && ! $this->isKnownHost($host, $port)) {
            return $url;
        }

        $path = $parts['path'] ?? '';

        // Normalize backslashes so a same-host "/\evil.com/x" can't survive as a
        // protocol-relative link — browsers treat "\" as "/" for http(s), so it
        // would otherwise navigate off-site. The "//" collapse below finishes it.
        $path = str_replace('\\', '/', $path);

        // Relative path without a leading slash (contact, ./x, ../x) is ambiguous.
        if ($host === null && ! str_starts_with($path, '/')) {
            return $url;
        }

        // Asset paths (e.g. the public storage dir) are not locale-scoped page
        // routes — leave them exactly as authored so they keep resolving.
        if ($this->isSkippablePath($path)) {
            return $url;
        }

        $suffix = (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');

        $bare = $this->stripLeadingPrefix($path, $source, $target);
        $bareTrimmed = ltrim($bare, '/');
        $prefix = filled($target->prefix) ? trim((string) $target->prefix, '/') : '';

        // Root/home link → "/ru" or "/", never a trailing-slash "/ru/" (keeps
        // parity with Site->url and avoids duplicate-URL variants).
        $targetPath = $bareTrimmed === ''
            ? '/' . $prefix
            : '/' . ($prefix !== '' ? $prefix . '/' : '') . $bareTrimmed;
        $targetPath = (string) preg_replace('#/{2,}#', '/', $targetPath);
        $targetPath .= $suffix;

        if ($this->targetHasOwnDomain($target)) {
            return rtrim($target->hostWithScheme, '/') . $targetPath;
        }

        return $targetPath;
    }

    /**
     * True for links carrying a scheme we must not touch (mailto:, tel:,
     * javascript:, ftp:, ...). http/https and protocol-relative "//host" are not
     * considered non-http here — those get rewritten.
     */
    private function hasNonHttpScheme(string $url): bool
    {
        if (str_starts_with($url, '//')) {
            return false;
        }

        if (preg_match('#^([a-zA-Z][a-zA-Z0-9+.\-]*):#', $url, $matches) === 1) {
            return ! in_array(strtolower($matches[1]), ['http', 'https'], true);
        }

        return false;
    }

    private function isKnownHost(string $host, ?int $port): bool
    {
        return in_array($this->normalizeHostPort($host, $port), $this->knownHosts(), true);
    }

    /**
     * Lowercase, strip a leading "www." and append the port — so host matching is
     * case-insensitive, treats www/non-www as the same site, and does NOT treat a
     * different port on our host as internal.
     */
    private function normalizeHostPort(string $host, ?int $port): string
    {
        $host = strtolower($host);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $port !== null ? $host . ':' . $port : $host;
    }

    /**
     * Hosts we consider "ours": the app URL host plus every Site with an
     * explicit domain, each normalized to host[:port].
     *
     * @return array<int, string>
     */
    private function knownHosts(): array
    {
        if ($this->hosts !== null) {
            return $this->hosts;
        }

        $hosts = [];

        $appParts = parse_url((string) config('app.url'));
        if (! empty($appParts['host'])) {
            $hosts[] = $this->normalizeHostPort($appParts['host'], $appParts['port'] ?? null);
        }

        foreach (Site::query()->whereNotNull('domain')->pluck('domain') as $domain) {
            if (! filled($domain)) {
                continue;
            }

            // Site->domain is stored as "host" or "host:port" (no scheme).
            $parts = parse_url('//' . ltrim((string) $domain, '/'));
            $hosts[] = $this->normalizeHostPort($parts['host'] ?? (string) $domain, $parts['port'] ?? null);
        }

        return $this->hosts = array_values(array_unique($hosts));
    }

    /**
     * True when the first path segment is a non-page area we must not
     * locale-prefix (e.g. the public storage dir). Configurable via
     * fsp.transfer.skip_path_prefixes.
     */
    private function isSkippablePath(string $path): bool
    {
        $segments = explode('/', ltrim($path, '/'));
        $first = $segments[0];

        if ($first === '') {
            return false;
        }

        /** @var array<int, string> $skip */
        $skip = config('fsp.transfer.skip_path_prefixes', ['storage']);

        return in_array($first, $skip, true);
    }

    private function stripLeadingPrefix(string $path, Site $source, Site $target): string
    {
        $segments = explode('/', ltrim($path, '/'));
        $first = $segments[0];

        if ($first !== '' && in_array($first, $this->prefixesToStrip($source, $target), true)) {
            array_shift($segments);

            return '/' . implode('/', $segments);
        }

        return '/' . ltrim($path, '/');
    }

    /**
     * Locale prefixes that may lead an existing internal link: every active
     * site's prefix, plus the source's and target's own — so the rewrite is
     * idempotent even when either site is currently inactive (not in the
     * active-only knownPrefixes list).
     *
     * @return array<int, string>
     */
    private function prefixesToStrip(Site $source, Site $target): array
    {
        $prefixes = $this->knownPrefixes();

        foreach ([$source->prefix, $target->prefix] as $prefix) {
            if (filled($prefix)) {
                $prefixes[] = trim((string) $prefix, '/');
            }
        }

        return array_values(array_unique($prefixes));
    }

    /**
     * Queried fresh (not the route provider's process-static cache) so it stays
     * correct across tests and after site changes within a request.
     *
     * @return array<int, string>
     */
    private function knownPrefixes(): array
    {
        if ($this->prefixes !== null) {
            return $this->prefixes;
        }

        return $this->prefixes = Site::active()
            ->whereNotNull('prefix')
            ->pluck('prefix')
            ->filter(fn ($prefix) => filled($prefix))
            ->map(fn ($prefix) => trim((string) $prefix, '/'))
            ->unique()
            ->values()
            ->all();
    }

    private function targetHasOwnDomain(Site $target): bool
    {
        if (! filled($target->domain)) {
            return false;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return strtolower((string) $target->domain) !== strtolower((string) $appHost);
    }
}
