<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Services;

use Zoker\FilamentStaticPages\Ai\Agents\TranslateAgent;
use Zoker\FilamentStaticPages\Services\Translator;
use Zoker\FilamentStaticPages\Tests\TestCase;

class TranslatorTest extends TestCase
{
    public function test_it_maps_translations_back_to_their_keys(): void
    {
        TranslateAgent::fake(fn () => ['items' => [
            ['key' => 't0', 'translation' => 'Bonjour'],
            ['key' => 't1', 'translation' => '<p>Monde</p>'],
        ]]);

        $result = (new Translator)->translate(
            ['t0' => 'Hello', 't1' => '<p>World</p>'],
            'en',
            'fr',
        );

        expect($result)->toBe(['t0' => 'Bonjour', 't1' => '<p>Monde</p>']);
    }

    public function test_it_returns_empty_for_no_input_without_calling_the_agent(): void
    {
        TranslateAgent::fake()->preventStrayPrompts();

        expect((new Translator)->translate([], 'en', 'fr'))->toBe([]);

        TranslateAgent::assertNeverPrompted();
    }

    public function test_it_injects_site_context_into_instructions(): void
    {
        config(['fsp.ai.context' => 'An online hookah shop']);

        expect((new TranslateAgent('en', 'fr'))->instructions())
            ->toContain('An online hookah shop');
    }

    public function test_it_sends_html_without_escaping_slashes(): void
    {
        TranslateAgent::fake(fn () => ['items' => []]);

        (new Translator)->translate(['t0' => '<p>Hi</p>'], 'en', 'fr');

        TranslateAgent::assertPrompted(
            fn ($prompt) => str_contains($prompt->prompt, '</p>') && ! str_contains($prompt->prompt, '<\/p>')
        );
    }

    public function test_it_unescapes_stray_slashes_returned_by_the_model(): void
    {
        TranslateAgent::fake(fn () => ['items' => [['key' => 't0', 'translation' => '<p>Zdravo<\/p>']]]);

        $result = (new Translator)->translate(['t0' => '<p>Hi</p>'], 'en', 'sl');

        expect($result['t0'])->toBe('<p>Zdravo</p>');
    }
}
