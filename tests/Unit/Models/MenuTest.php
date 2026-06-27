<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Models;

use Zoker\FilamentStaticPages\Models\Menu;
use Zoker\FilamentStaticPages\Tests\TestCase;

class MenuTest extends TestCase
{
    public function test_cache_key_is_lowercased_and_namespaced(): void
    {
        $this->assertEquals('fsp-menu:main', Menu::getCacheKey('Main'));
    }

    public function test_get_menu_returns_the_menu_by_code_and_caches_it(): void
    {
        $menu = new Menu;
        $menu->code = 'footer';
        $menu->save();

        $resolved = Menu::getMenu('footer');
        $this->assertEquals('footer', $resolved->code);
        $this->assertTrue(cache()->has(Menu::getCacheKey('footer')));
    }

    public function test_get_menu_returns_an_empty_menu_when_missing(): void
    {
        $resolved = Menu::getMenu('does-not-exist');

        $this->assertInstanceOf(Menu::class, $resolved);
        $this->assertNull($resolved->code);
    }

    public function test_saving_a_menu_forgets_its_cache(): void
    {
        $menu = new Menu;
        $menu->code = 'header';
        $menu->save();

        Menu::getMenu('header');
        $this->assertTrue(cache()->has(Menu::getCacheKey('header')));

        $menu->save();
        $this->assertFalse(cache()->has(Menu::getCacheKey('header')));
    }

    public function test_get_url_returns_a_plain_url_for_the_default_type(): void
    {
        $menu = new Menu;

        $url = $menu->getUrl([
            'url' => [
                'type' => 'custom',
                'data' => ['url' => 'https://example.test/page'],
            ],
        ]);

        $this->assertEquals('https://example.test/page', $url);
    }

    public function test_get_params_for_route_merges_param_groups(): void
    {
        $params = Menu::getParamsForRoute([
            'data' => ['params' => [['a' => 1], ['b' => 2]]],
        ]);

        $this->assertEquals(['a' => 1, 'b' => 2], $params);
    }

    public function test_get_params_for_route_is_empty_without_params(): void
    {
        $this->assertEquals([], Menu::getParamsForRoute(['data' => []]));
    }
}
