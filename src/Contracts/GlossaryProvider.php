<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Contracts;

/**
 * Supplies the translator glossary entries consumed by GlossaryPromptBuilder.
 *
 * FilamentStaticPages binds a config-backed default (works standalone). When
 * zoker/shop is installed it rebinds this to a database-backed implementation,
 * so the same renderer feeds both packages from one source. A host app that wants
 * its own provider must bind it in the BOOT phase of its service provider, so it
 * runs after (and overrides) shop's boot-phase rebind.
 *
 * Each entry is a plain array:
 *   [
 *     'locale'           => ?string, // target language; null = all languages
 *     'term'             => string,  // the source word / phrase
 *     'translation'      => ?string, // forced translation for this locale
 *     'note'             => ?string, // sense disambiguation shown to the AI
 *     'do_not_translate' => bool,    // keep verbatim (product/brand name)
 *   ]
 */
interface GlossaryProvider
{
    /**
     * @return array<int, array{locale?: string|null, term: string, translation?: string|null, note?: string|null, do_not_translate?: bool}>
     */
    public function entries(): array;
}
