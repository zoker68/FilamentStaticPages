<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Services;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Enums\ContentType;
use Zoker\FilamentStaticPages\Models\Content;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Services\BlocksExportImportService;
use Zoker\FilamentStaticPages\Tests\TestCase;

class BlocksExportImportServiceTest extends TestCase
{
    private BlocksExportImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BlocksExportImportService;
    }

    private function makePage(Site $site, array $attributes = [], array $content = []): Page
    {
        $page = new Page(array_merge([
            'name' => 'Home',
            'url' => 'home',
            'layout' => 'app',
            'published' => true,
        ], $attributes));
        $page->setSite($site);
        $page->content = $content;
        $page->save();

        return $page;
    }

    public function test_export_blocks_for_a_page(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        $blocks = [['type' => 'Heading', 'data' => ['text' => 'Hi']]];
        $page = $this->makePage($site, ['name' => 'About', 'url' => 'about'], $blocks);

        $data = $this->service->exportBlocks($page);

        expect($data['version'])->toBe(BlocksExportImportService::VERSION)
            ->and($data['type'])->toBe(ContentType::Page->value)
            ->and($data['source_site_id'])->toBe($site->id)
            ->and($data['meta'])->toMatchArray(['name' => 'About', 'url' => 'about', 'layout' => 'app'])
            ->and($data['blocks'])->toBe($blocks);
    }

    public function test_export_blocks_for_content_in_a_locale(): void
    {
        $content = new Content;
        $content->code = 'promo';
        $content->setTranslation('content', 'en', [['type' => 'Banner']]);
        $content->save();

        $data = $this->service->exportBlocks($content, 'en');

        expect($data['type'])->toBe(ContentType::Content->value)
            ->and($data['locale'])->toBe('en')
            ->and($data['meta'])->toBe(['code' => 'promo'])
            ->and($data['blocks'])->toBe([['type' => 'Banner']]);
    }

    public function test_import_as_page_creates_a_page_with_the_blocks(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        $data = [
            'version' => '1.0',
            'type' => 'page',
            'meta' => ['name' => 'Imported', 'url' => 'landing', 'layout' => 'app', 'published' => true],
            'blocks' => [['type' => 'Heading']],
        ];

        $page = $this->service->importAsPage($data, $site, publish: true);

        expect($page->exists)->toBeTrue()
            ->and($page->site_id)->toBe($site->id)
            ->and($page->url)->toBe('landing')
            ->and($page->published)->toBeTrue()
            ->and($page->content)->toBe([['type' => 'Heading']]);
    }

    public function test_import_as_page_is_unpublished_when_not_publishing(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        $data = ['version' => '1.0', 'meta' => ['name' => 'X', 'url' => 'x', 'published' => true], 'blocks' => []];

        $page = $this->service->importAsPage($data, $site, publish: false);

        expect($page->published)->toBeFalse();
    }

    public function test_import_as_page_generates_a_unique_url_on_collision(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        $this->makePage($site, ['url' => 'docs']);

        $data = ['version' => '1.0', 'meta' => ['name' => 'Docs', 'url' => 'docs'], 'blocks' => []];
        $page = $this->service->importAsPage($data, $site);

        expect($page->url)->toBe('docs-1');
    }

    public function test_import_as_content_generates_a_unique_code(): void
    {
        $existing = new Content;
        $existing->code = 'shared';
        $existing->save();

        $data = ['version' => '1.0', 'type' => 'content', 'locale' => 'en', 'meta' => ['code' => 'shared'], 'blocks' => [['type' => 'A']]];
        $content = $this->service->importAsContent($data);

        expect($content->code)->toBe('shared-1')
            ->and($content->getTranslation('content', 'en', false))->toBe([['type' => 'A']]);
    }

    public function test_validate_import_data(): void
    {
        expect($this->service->validateImportData(['version' => '1.0', 'blocks' => []]))->toBeTrue()
            ->and($this->service->validateImportData(['blocks' => []]))->toBeFalse()
            ->and($this->service->validateImportData(['version' => '1.0']))->toBeFalse();
    }

    public function test_get_import_type(): void
    {
        expect($this->service->getImportType(['type' => 'page']))->toBe(ContentType::Page)
            ->and($this->service->getImportType(['type' => 'content']))->toBe(ContentType::Content)
            ->and($this->service->getImportType(['type' => 'bogus']))->toBeNull()
            ->and($this->service->getImportType([]))->toBeNull();
    }

    public function test_json_export_and_parse_round_trip(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        $page = $this->makePage($site, ['url' => 'json'], [['type' => 'Meta']]);

        $json = $this->service->exportToJson($page);
        $parsed = $this->service->parseJsonImport($json);

        expect($parsed)->not->toBeNull()
            ->and($parsed['blocks'])->toBe([['type' => 'Meta']]);
    }

    public function test_parse_json_import_rejects_invalid_payloads(): void
    {
        expect($this->service->parseJsonImport('{not json'))->toBeNull()
            ->and($this->service->parseJsonImport('{"foo":"bar"}'))->toBeNull();
    }

    public function test_copy_blocks_to_existing_page_replaces_or_merges(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        $source = $this->makePage($site, ['url' => 'src'], [['type' => 'B']]);
        $target = $this->makePage($site, ['url' => 'dst'], [['type' => 'A']]);

        $this->service->copyBlocksToExisting($source, $target, replaceContent: false);
        expect($target->fresh()->content)->toBe([['type' => 'A'], ['type' => 'B']]);

        $this->service->copyBlocksToExisting($source, $target, replaceContent: true);
        expect($target->fresh()->content)->toBe([['type' => 'B']]);
    }

    public function test_import_blocks_to_existing_content_replaces_or_merges(): void
    {
        $target = new Content;
        $target->code = 'target';
        $target->setTranslation('content', 'en', [['type' => 'A']]);
        $target->save();

        $data = ['version' => '1.0', 'blocks' => [['type' => 'B']]];

        $this->service->importBlocksToExistingContent($data, $target, 'en', replaceContent: false);
        expect($target->fresh()->getTranslation('content', 'en', false))->toBe([['type' => 'A'], ['type' => 'B']]);

        $this->service->importBlocksToExistingContent($data, $target, 'en', replaceContent: true);
        expect($target->fresh()->getTranslation('content', 'en', false))->toBe([['type' => 'B']]);
    }
}
