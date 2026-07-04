<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Services;

use Zoker\FilamentStaticPages\Contracts\GlossaryProvider;

/**
 * Default glossary provider — reads entries from `fsp.ai.glossary` config. Lets
 * FilamentStaticPages use a glossary standalone (without zoker/shop): the host
 * app declares the array in config. Returns an empty glossary when unset.
 */
class ConfigGlossaryProvider implements GlossaryProvider
{
    public function entries(): array
    {
        $rows = config('fsp.ai.glossary', []);

        if (! is_array($rows)) {
            return [];
        }

        $entries = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['term']) || ! is_string($row['term'])) {
                continue;
            }

            $term = trim($row['term']);
            if ($term === '') {
                continue;
            }

            $entries[] = [
                'locale' => isset($row['locale']) && is_string($row['locale']) ? $row['locale'] : null,
                'term' => $term,
                'translation' => isset($row['translation']) && is_string($row['translation']) ? $row['translation'] : null,
                'note' => isset($row['note']) && is_string($row['note']) ? $row['note'] : null,
                'do_not_translate' => (bool) ($row['do_not_translate'] ?? false),
            ];
        }

        return $entries;
    }
}
