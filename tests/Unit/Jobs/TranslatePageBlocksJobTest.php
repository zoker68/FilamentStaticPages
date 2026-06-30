<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Jobs;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Jobs\TranslatePageBlocksJob;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Services\Translator;
use Zoker\FilamentStaticPages\Tests\TestCase;

class TranslatePageBlocksJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['fsp.ai.enabled' => true, 'fsp.ai.base_locale' => 'en']);

        // Stub translator: prefixes each value with the target locale.
        $this->app->instance(Translator::class, new class extends Translator
        {
            public function translate(array $texts, string $sourceLocale, string $targetLocale): array
            {
                $out = [];
                foreach ($texts as $key => $value) {
                    $out[$key] = $targetLocale . ':' . $value;
                }

                return $out;
            }
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $content
     */
    private function makePage(array $content): Page
    {
        $site = Site::factory()->create(['is_active' => true, 'locale' => 'en']);

        $page = new Page(['name' => 'P', 'url' => 'p', 'layout' => 'app', 'published' => true]);
        $page->setSite($site);
        $page->content = $content;
        $page->save();

        return $page;
    }

    public function test_it_translates_the_whole_page_from_index_zero(): void
    {
        $page = $this->makePage([
            ['type' => 'Heading', 'data' => ['heading' => 'A']],
            ['type' => 'Content', 'data' => ['content' => '<p>B</p>']],
        ]);

        (new TranslatePageBlocksJob($page->id, 'en', 'de', 0))->handle();

        $content = $page->fresh()->content;
        expect($content[0]['data']['heading'])->toBe('de:A')
            ->and($content[1]['data']['content'])->toBe('de:<p>B</p>');
    }

    public function test_it_only_translates_the_appended_tail(): void
    {
        $page = $this->makePage([
            ['type' => 'Heading', 'data' => ['heading' => 'Existing']],
            ['type' => 'Heading', 'data' => ['heading' => 'Copied 1']],
            ['type' => 'Heading', 'data' => ['heading' => 'Copied 2']],
        ]);

        (new TranslatePageBlocksJob($page->id, 'en', 'de', 1))->handle();

        $content = $page->fresh()->content;
        expect($content[0]['data']['heading'])->toBe('Existing')
            ->and($content[1]['data']['heading'])->toBe('de:Copied 1')
            ->and($content[2]['data']['heading'])->toBe('de:Copied 2');
    }

    public function test_it_skips_when_source_is_not_the_main_language(): void
    {
        $page = $this->makePage([['type' => 'Heading', 'data' => ['heading' => 'A']]]);

        // Source 'sl' is not the main language -> no translation.
        (new TranslatePageBlocksJob($page->id, 'sl', 'de', 0))->handle();

        expect($page->fresh()->content[0]['data']['heading'])->toBe('A');
    }

    public function test_it_skips_when_ai_is_disabled(): void
    {
        config(['fsp.ai.enabled' => false]);

        $page = $this->makePage([['type' => 'Heading', 'data' => ['heading' => 'A']]]);

        (new TranslatePageBlocksJob($page->id, 'en', 'de', 0))->handle();

        expect($page->fresh()->content[0]['data']['heading'])->toBe('A');
    }
}
