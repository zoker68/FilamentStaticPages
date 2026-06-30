<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Translates a batch of labelled strings from one locale to another, returning
 * each translation against its original key so the caller can re-insert them.
 */
class TranslateAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        public string $sourceLocale,
        public string $targetLocale,
    ) {}

    public function instructions(): string
    {
        $context = trim((string) config('fsp.ai.context'));
        $context = $context !== ''
            ? "The website's topic: {$context}. Use it to resolve ambiguous terms and translate them in the correct domain-specific sense. "
            : '';

        return "You are a professional translator for the website '" . config('app.name') . "'. "
            . $context
            . 'You receive a JSON object with an `items` array; each item has a `key` and a `text`. '
            . "Translate every `text` from locale {$this->sourceLocale} to locale {$this->targetLocale}. "
            . 'Preserve all HTML tags, attributes and placeholders exactly as they appear. '
            . 'Return every item with its original `key` unchanged and the translated value in `translation`. '
            . 'Do not add, remove, merge or reorder items.';
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'items' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'key' => $schema->string()->required(),
                    'translation' => $schema->string()->required(),
                ])
            )->required(),
        ];
    }
}
