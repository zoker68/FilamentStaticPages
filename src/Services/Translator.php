<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Services;

use Zoker\FilamentStaticPages\Ai\Agents\TranslateAgent;

/**
 * Thin adapter over the Laravel AI SDK: translates a flat [key => text] map and
 * returns the same keys with translated values. The provider/model are taken
 * from the `fsp.ai` config so the host app can switch provider without code.
 */
class Translator
{
    /**
     * @param  array<string, string>  $texts
     * @return array<string, string>
     */
    public function translate(array $texts, string $sourceLocale, string $targetLocale): array
    {
        if ($texts === []) {
            return [];
        }

        $items = [];
        foreach ($texts as $key => $text) {
            $items[] = ['key' => (string) $key, 'text' => $text];
        }

        $response = (new TranslateAgent($sourceLocale, $targetLocale))->prompt(
            json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            provider: config('fsp.ai.provider'),
            model: config('fsp.ai.model'),
        );

        $out = [];
        foreach ($response['items'] ?? [] as $item) {
            if (isset($item['key'], $item['translation'])) {
                $out[(string) $item['key']] = $this->unescapeSlashes((string) $item['translation']);
            }
        }

        return $out;
    }

    /**
     * Some models echo JSON-escaped slashes ("<\/p>") into the translated value;
     * restore them so the stored HTML stays valid.
     */
    private function unescapeSlashes(string $value): string
    {
        return str_replace('\/', '/', $value);
    }
}
