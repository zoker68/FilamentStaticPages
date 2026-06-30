<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Services;

use Zoker\FilamentStaticPages\Services\BlockContentTranslator;
use Zoker\FilamentStaticPages\Services\Translator;
use Zoker\FilamentStaticPages\Tests\TestCase;

class BlockContentTranslatorTest extends TestCase
{
    private function translator(): BlockContentTranslator
    {
        // Stub translator: prefixes every value with the target locale so we can
        // assert exactly which leaves were translated and that HTML is untouched.
        $fake = new class extends Translator
        {
            public function translate(array $texts, string $sourceLocale, string $targetLocale): array
            {
                $out = [];
                foreach ($texts as $key => $value) {
                    $out[$key] = $targetLocale . ':' . $value;
                }

                return $out;
            }
        };

        return new BlockContentTranslator($fake);
    }

    public function test_it_translates_only_declared_text_fields(): void
    {
        $blocks = [
            ['type' => 'Heading', 'data' => ['heading' => 'Hello', 'size' => 'h1', 'css_class' => 'text-center']],
            ['type' => 'Banner', 'data' => ['alt' => 'Logo', 'link' => 'https://x', 'target' => '_blank', 'image' => 'banners/a.jpg']],
            ['type' => 'Content', 'data' => ['content' => '<p>Body</p>', 'css_class' => '']],
        ];

        $result = $this->translator()->translate($blocks, 'en', 'de');

        // Translated text.
        expect($result[0]['data']['heading'])->toBe('de:Hello')
            ->and($result[1]['data']['alt'])->toBe('de:Logo')
            ->and($result[2]['data']['content'])->toBe('de:<p>Body</p>');

        // Untouched non-text fields.
        expect($result[0]['data']['size'])->toBe('h1')
            ->and($result[0]['data']['css_class'])->toBe('text-center')
            ->and($result[1]['data']['link'])->toBe('https://x')
            ->and($result[1]['data']['target'])->toBe('_blank')
            ->and($result[1]['data']['image'])->toBe('banners/a.jpg');
    }

    public function test_it_translates_nested_repeater_items(): void
    {
        $blocks = [
            ['type' => 'QuestionAnswer', 'data' => ['categories' => [
                ['title' => 'Cat', 'questions' => [
                    ['question' => 'Q1', 'answer' => 'A1'],
                    ['question' => 'Q2', 'answer' => 'A2'],
                ]],
            ]]],
            ['type' => 'ImageWithText', 'data' => ['template' => 'small-icon', 'blocks' => [
                [
                    'heading' => 'Feat',
                    'text' => '<p>desc</p>',
                    'image_type' => 'icon',
                    'icon' => 'heroicon-o-star',
                    'link' => ['text' => 'More', 'url' => 'https://y', 'target' => '_self'],
                ],
            ]]],
        ];

        $result = $this->translator()->translate($blocks, 'en', 'de');

        $qa = $result[0]['data']['categories'][0];
        expect($qa['title'])->toBe('de:Cat')
            ->and($qa['questions'][0]['question'])->toBe('de:Q1')
            ->and($qa['questions'][0]['answer'])->toBe('de:A1')
            ->and($qa['questions'][1]['question'])->toBe('de:Q2')
            ->and($qa['questions'][1]['answer'])->toBe('de:A2');

        $iwt = $result[1]['data']['blocks'][0];
        expect($iwt['heading'])->toBe('de:Feat')
            ->and($iwt['text'])->toBe('de:<p>desc</p>')
            ->and($iwt['link']['text'])->toBe('de:More')
            // Untouched.
            ->and($iwt['link']['url'])->toBe('https://y')
            ->and($iwt['icon'])->toBe('heroicon-o-star')
            ->and($iwt['image_type'])->toBe('icon')
            ->and($result[1]['data']['template'])->toBe('small-icon');
    }

    public function test_it_returns_blocks_unchanged_when_locales_match(): void
    {
        $blocks = [['type' => 'Heading', 'data' => ['heading' => 'Hello']]];

        expect($this->translator()->translate($blocks, 'en', 'en'))->toBe($blocks);
    }

    public function test_it_skips_empty_and_unknown_blocks(): void
    {
        $blocks = [
            ['type' => 'Heading', 'data' => ['heading' => '']],
            ['type' => 'Heading', 'data' => ['heading' => '<p></p>']],
            ['type' => 'TotallyUnknown', 'data' => ['heading' => 'Hi']],
            ['type' => 'Heading'],
        ];

        $result = $this->translator()->translate($blocks, 'en', 'de');

        expect($result[0]['data']['heading'])->toBe('')
            ->and($result[1]['data']['heading'])->toBe('<p></p>')
            ->and($result[2]['data']['heading'])->toBe('Hi')
            ->and($result)->toBe($blocks);
    }
}
