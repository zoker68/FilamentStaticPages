<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Support;

use Zoker\FilamentStaticPages\Support\SeoTitle;
use Zoker\FilamentStaticPages\Tests\TestCase;

class SeoTitleTest extends TestCase
{
    // The tail used to be built from app.name, which is "Laravel" on an untouched .env.
    public function test_it_builds_the_title_tail_from_the_site_name(): void
    {
        config()->set('app.name', 'Laravel');
        config()->set('fsp.site_name', 'Widget Store');

        expect(SeoTitle::withSuffix('About us'))->toBe('About us | Widget Store');
    }
}
