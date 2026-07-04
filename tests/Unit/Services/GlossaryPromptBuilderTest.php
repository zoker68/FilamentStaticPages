<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Services;

use Zoker\FilamentStaticPages\Ai\Agents\SeoAgent;
use Zoker\FilamentStaticPages\Ai\Agents\TranslateAgent;
use Zoker\FilamentStaticPages\Services\GlossaryPromptBuilder;
use Zoker\FilamentStaticPages\Tests\TestCase;

/**
 * Standalone glossary behaviour: no zoker/shop here, so the default
 * config-backed provider (fsp.ai.glossary) drives the prompt.
 */
class GlossaryPromptBuilderTest extends TestCase
{
    private function builder(): GlossaryPromptBuilder
    {
        return app(GlossaryPromptBuilder::class);
    }

    public function test_empty_glossary_returns_empty_string(): void
    {
        config()->set('fsp.ai.glossary', []);

        $this->assertSame('', $this->builder()->build(['ru']));
    }

    public function test_it_renders_config_glossary_per_language(): void
    {
        config()->set('fsp.ai.glossary', [
            ['term' => 'bit', 'locale' => 'ru', 'translation' => 'фреза', 'note' => 'a nail drill bit'],
            ['term' => 'bit', 'locale' => 'sl', 'translation' => 'nastavek'],
            ['term' => 'Magic Bits', 'do_not_translate' => true],
        ]);

        $prompt = $this->builder()->build(['ru']);

        $this->assertStringContainsString('фреза', $prompt);
        $this->assertStringContainsString('nail drill', $prompt);
        $this->assertStringContainsString('Magic Bits', $prompt);
        $this->assertStringNotContainsString('nastavek', $prompt);
        $this->assertStringContainsString('precedence', $prompt);
    }

    public function test_it_injects_glossary_into_the_translate_agent(): void
    {
        config()->set('fsp.ai.glossary', [
            ['term' => 'bit', 'locale' => 'ru', 'translation' => 'фреза'],
        ]);

        $this->assertStringContainsString('фреза', (new TranslateAgent('en', 'ru'))->instructions());
    }

    public function test_it_injects_glossary_into_the_seo_agent(): void
    {
        config()->set('fsp.ai.glossary', [
            ['term' => 'Magic Bits', 'do_not_translate' => true],
        ]);

        $this->assertStringContainsString('Magic Bits', (new SeoAgent('ru'))->instructions());
    }

    public function test_it_skips_malformed_config_rows(): void
    {
        config()->set('fsp.ai.glossary', [
            'not-an-array',
            ['no_term_key' => true],
            ['term' => '   '],
            ['term' => 'bit', 'locale' => 'ru', 'translation' => 'фреза'],
        ]);

        $prompt = $this->builder()->build(['ru']);

        $this->assertStringContainsString('фреза', $prompt);
        $this->assertStringNotContainsString('- ""', $prompt);
    }

    public function test_empty_target_locales_includes_every_language(): void
    {
        config()->set('fsp.ai.glossary', [
            ['term' => 'bit', 'locale' => 'ru', 'translation' => 'фреза'],
            ['term' => 'bit', 'locale' => 'sl', 'translation' => 'nastavek'],
        ]);

        $prompt = $this->builder()->build([]);

        $this->assertStringContainsString('фреза', $prompt);
        $this->assertStringContainsString('nastavek', $prompt);
    }

    public function test_it_uses_the_configured_ai_timeout(): void
    {
        config()->set('fsp.ai.timeout', 200);

        $this->assertSame(200, (new SeoAgent('ru'))->timeout());
        $this->assertSame(200, (new TranslateAgent('en', 'ru'))->timeout());
    }
}
