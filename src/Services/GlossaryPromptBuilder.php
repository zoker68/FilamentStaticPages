<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Services;

use Illuminate\Support\Facades\Log;
use Throwable;
use Zoker\FilamentStaticPages\Contracts\GlossaryProvider;

/**
 * Renders the translator glossary into a prompt fragment appended to the
 * Translate/SEO agent instructions, using whichever GlossaryProvider is bound
 * (config-backed by default, database-backed when zoker/shop is installed).
 *
 * The glossary is broken down per target language — different languages have
 * different problematic phrases — plus a global "do not translate" list:
 *
 *  - "do not translate" names — product/brand names kept verbatim, given
 *    precedence over term rules so a name like "Magic Bits" is not broken up
 *    even when "bit" has a forced translation.
 *  - per-locale term rules — a forced translation and/or a meaning note so the AI
 *    picks the correct domain sense (e.g. "bit" = nail drill bit, not the computing term).
 */
class GlossaryPromptBuilder
{
    /**
     * @param  array<int, string>  $targetLocales  Locales the prompt targets. Per-locale
     *                                             rules are limited to these; the global
     *                                             do-not-translate list is always included.
     *                                             Empty = include every locale.
     */
    public function build(array $targetLocales = []): string
    {
        $entries = $this->entries();

        if ($entries === []) {
            return '';
        }

        $sections = [];

        $keepSection = $this->keepSection($entries);
        if ($keepSection !== '') {
            $sections[] = $keepSection;
        }

        $rulesSection = $this->rulesSection($entries, $targetLocales);
        if ($rulesSection !== '') {
            $sections[] = $rulesSection;
        }

        return $sections === [] ? '' : 'Glossary rules: ' . implode(' ', $sections);
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    private function keepSection(array $entries): string
    {
        $names = [];
        foreach ($entries as $entry) {
            if (! empty($entry['do_not_translate'])) {
                $names[$entry['term']] = $entry['term'];
            }
        }

        if ($names === []) {
            return '';
        }

        return 'Never translate the following names — keep them EXACTLY as written, '
            . 'they are product or brand names: "' . implode('", "', $names) . '". '
            . 'These names take precedence over any term rules: if such a name contains '
            . 'a word that also appears as a term rule, do NOT translate that word inside the name. '
            . 'Apply terminology rules to whole words only, not as substrings of other words.';
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @param  array<int, string>  $targetLocales
     */
    private function rulesSection(array $entries, array $targetLocales): string
    {
        $byLocale = [];
        foreach ($entries as $entry) {
            if (! empty($entry['do_not_translate']) || ! $this->hasContent($entry)) {
                continue;
            }

            $locale = isset($entry['locale']) && is_string($entry['locale']) ? $entry['locale'] : null;

            // Global rules always apply; per-locale rules only for the requested locales.
            if ($locale !== null && $targetLocales !== [] && ! in_array($locale, $targetLocales, true)) {
                continue;
            }

            $byLocale[$locale ?? ''][] = $this->renderEntry($entry);
        }

        if ($byLocale === []) {
            return '';
        }

        ksort($byLocale);

        $blocks = [];
        foreach ($byLocale as $locale => $lines) {
            $lines = array_values(array_unique($lines));

            $blocks[] = $locale === ''
                ? 'For all languages: ' . implode(' ', $lines)
                : "For locale {$locale}: " . implode(' ', $lines);
        }

        return 'Use this terminology exactly. For each entry, use the meaning to pick the correct '
            . 'sense, and where a translation is given use it verbatim. ' . implode(' ', $blocks);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function renderEntry(array $entry): string
    {
        $parts = ['- "' . $entry['term'] . '"'];

        $note = trim((string) ($entry['note'] ?? ''));
        if ($note !== '') {
            $parts[] = '(meaning: ' . $note . ')';
        }

        $translation = trim((string) ($entry['translation'] ?? ''));
        if ($translation !== '') {
            $parts[] = '→ "' . $translation . '"';
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function hasContent(array $entry): bool
    {
        return trim((string) ($entry['translation'] ?? '')) !== ''
            || trim((string) ($entry['note'] ?? '')) !== '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function entries(): array
    {
        try {
            return app(GlossaryProvider::class)->entries();
        } catch (Throwable $e) {
            // Never let glossary lookup break translation/SEO generation, but surface it.
            Log::warning('Glossary provider failed to resolve entries', ['exception' => $e]);

            return [];
        }
    }
}
