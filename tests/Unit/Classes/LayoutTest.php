<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Classes;

use Zoker\FilamentStaticPages\Classes\Layout;
use Zoker\FilamentStaticPages\Tests\TestCase;

class LayoutTest extends TestCase
{
    public function test_get_layout_component_prefixes_the_layout_name(): void
    {
        $this->assertEquals('layouts.app', Layout::getLayoutComponent('app'));
        $this->assertEquals('layouts.account', Layout::getLayoutComponent('account'));
    }

    public function test_get_layout_options_returns_an_array(): void
    {
        $this->assertIsArray(Layout::getLayoutOptions());
    }
}
