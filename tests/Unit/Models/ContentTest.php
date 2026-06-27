<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Models;

use Illuminate\Database\QueryException;
use Zoker\FilamentStaticPages\Models\Content;
use Zoker\FilamentStaticPages\Tests\TestCase;

class ContentTest extends TestCase
{
    public function test_it_stores_translatable_content_per_locale(): void
    {
        $content = new Content(['code' => 'home']);
        $content->setTranslation('content', 'en', ['heading' => 'Welcome']);
        $content->setTranslation('content', 'ru', ['heading' => 'Добро пожаловать']);
        $content->save();

        $fresh = Content::find($content->id);

        $this->assertEquals(['heading' => 'Welcome'], $fresh->getTranslation('content', 'en'));
        $this->assertEquals(['heading' => 'Добро пожаловать'], $fresh->getTranslation('content', 'ru'));
    }

    public function test_code_must_be_unique(): void
    {
        Content::create(['code' => 'duplicate']);

        $this->expectException(QueryException::class);

        Content::create(['code' => 'duplicate']);
    }

    public function test_it_uses_the_configured_table_prefix(): void
    {
        $this->assertEquals(config('fsp.table_prefix') . 'content', (new Content)->getTable());
    }
}
