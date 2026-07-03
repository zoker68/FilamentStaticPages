<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Support;

/**
 * Expands a block "dot path" (relative to a block's `data`) that may contain
 * "*" wildcards into the concrete dot paths that actually exist in the data.
 * Shared by BlockContentTranslator and BlockLinkRewriter so both walk the JSON
 * structure identically. Only paths that really exist are returned.
 */
final class BlockDataPaths
{
    /**
     * @return array<int, string>
     */
    public static function expand(mixed $data, string $dotPath): array
    {
        return self::expandSegments($data, explode('.', $dotPath));
    }

    /**
     * @param  array<int, string>  $segments
     * @return array<int, string>
     */
    private static function expandSegments(mixed $data, array $segments): array
    {
        if ($segments === []) {
            return [''];
        }

        $segment = $segments[0];
        $rest = array_slice($segments, 1);

        if ($segment === '*') {
            if (! is_array($data)) {
                return [];
            }

            $paths = [];
            foreach ($data as $index => $item) {
                foreach (self::expandSegments($item, $rest) as $sub) {
                    $paths[] = self::join((string) $index, $sub);
                }
            }

            return $paths;
        }

        if (! is_array($data) || ! array_key_exists($segment, $data)) {
            return [];
        }

        $paths = [];
        foreach (self::expandSegments($data[$segment], $rest) as $sub) {
            $paths[] = self::join($segment, $sub);
        }

        return $paths;
    }

    private static function join(string $head, string $tail): string
    {
        return $tail === '' ? $head : $head . '.' . $tail;
    }
}
