<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Generates an SEO title and meta description (in the given locale) for a page,
 * from the page form data passed as the prompt.
 */
class SeoAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public string $locale) {}

    public function instructions(): string
    {
        $context = trim((string) config('fsp.ai.context'));
        $context = $context !== ''
            ? "The website's topic: {$context}. Use it to keep the SEO copy on-topic and accurate. "
            : '';

        return "You are an SEO expert for the website '" . config('app.name') . "'. "
            . $context
            . "Given the page data as JSON, write an SEO-friendly title and meta description in locale {$this->locale}. "
            . 'The title must NOT include the site name (it is appended automatically) and be at most 60 characters. '
            . 'The meta description must be at most 160 characters.';
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'description' => $schema->string()->required(),
        ];
    }
}
