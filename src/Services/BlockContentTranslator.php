<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Services;

use Illuminate\Support\Arr;
use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;

/**
 * Translates the human-readable text inside a blocks array from one locale to
 * another. Which fields hold translatable text is declared per block via the
 * static $translatable property on each block component, so the structure and
 * all non-text fields (urls, image paths, enums, css classes, ...) are never
 * touched and the JSON shape is guaranteed to stay intact.
 */
class BlockContentTranslator
{
    /**
     * Max number of strings sent to the translator in a single request,
     * to avoid oversized payloads / token limits on large pages.
     */
    private const int CHUNK_SIZE = 25;

    public function __construct(private readonly Translator $translator) {}

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public function translate(array $blocks, string $sourceLocale, string $targetLocale): array
    {
        if ($sourceLocale === $targetLocale || $blocks === []) {
            return $blocks;
        }

        // 1. Collect translatable strings into a flat map keyed by a stable token.
        $texts = [];    // token => source text
        $map = [];      // token => [blockIndex, concretePath]
        $token = 0;

        foreach ($blocks as $blockIndex => $block) {
            $type = $block['type'] ?? null;
            $data = $block['data'] ?? null;

            if (! is_string($type) || ! is_array($data)) {
                continue;
            }

            foreach ($this->translatableKeysFor($type) as $keyPath) {
                foreach ($this->expandPath($data, explode('.', $keyPath)) as $concretePath) {
                    $value = Arr::get($data, $concretePath);

                    if (! $this->isTranslatableValue($value)) {
                        continue;
                    }

                    $key = 't' . $token++;
                    $texts[$key] = $value;
                    $map[$key] = [$blockIndex, $concretePath];
                }
            }
        }

        if ($texts === []) {
            return $blocks;
        }

        // 2. Translate in chunks. The translator never sees the structure,
        //    only a flat list of strings, so it cannot corrupt the JSON.
        $translated = [];
        foreach (array_chunk($texts, self::CHUNK_SIZE, true) as $chunk) {
            $translated += $this->translator->translate($chunk, $sourceLocale, $targetLocale);
        }

        // 3. Write translations back at their exact paths.
        foreach ($map as $key => [$blockIndex, $concretePath]) {
            $value = $translated[$key] ?? null;

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            Arr::set($blocks[$blockIndex]['data'], $concretePath, $value);
        }

        return $blocks;
    }

    /**
     * @return array<int, string>
     */
    private function translatableKeysFor(string $type): array
    {
        $class = BlocksComponentRegistry::get($type);

        if ($class === null || ! property_exists($class, 'translatable')) {
            return [];
        }

        /** @var array<int, string> */
        return $class::$translatable;
    }

    /**
     * Expand a dot path that may contain "*" wildcards into concrete dot paths
     * that actually exist in $data.
     *
     * @param  array<int, string>  $segments
     * @return array<int, string>
     */
    private function expandPath(mixed $data, array $segments): array
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
                foreach ($this->expandPath($item, $rest) as $sub) {
                    $paths[] = $this->joinPath((string) $index, $sub);
                }
            }

            return $paths;
        }

        if (! is_array($data) || ! array_key_exists($segment, $data)) {
            return [];
        }

        $paths = [];
        foreach ($this->expandPath($data[$segment], $rest) as $sub) {
            $paths[] = $this->joinPath($segment, $sub);
        }

        return $paths;
    }

    private function joinPath(string $head, string $tail): string
    {
        return $tail === '' ? $head : $head . '.' . $tail;
    }

    private function isTranslatableValue(mixed $value): bool
    {
        return is_string($value) && trim(strip_tags($value)) !== '';
    }
}
