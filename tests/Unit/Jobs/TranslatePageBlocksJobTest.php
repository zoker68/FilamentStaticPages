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

        config(['fsp.ai.enabled' => true]);

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

    public function test_it_translates_out_of_a_non_base_locale(): void
    {
        // Direction is decided at dispatch, so the job translates any source→target
        // pair. Regression: the removed base_locale gate would have skipped a
        // source locale other than the (old) main language.
        $page = $this->makePage([['type' => 'Heading', 'data' => ['heading' => 'A']]]);

        (new TranslatePageBlocksJob($page->id, 'ru', 'de', 0))->handle();

        expect($page->fresh()->content[0]['data']['heading'])->toBe('de:A');
    }

    public function test_it_skips_when_source_and_target_locales_match(): void
    {
        $page = $this->makePage([['type' => 'Heading', 'data' => ['heading' => 'A']]]);

        // Same source/target locale -> nothing to translate. (Direction — only OUT of
        // the default site — is now enforced at dispatch time, not in the job.)
        (new TranslatePageBlocksJob($page->id, 'en', 'en', 0))->handle();

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
