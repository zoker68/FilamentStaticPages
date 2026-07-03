<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Support;

use Zoker\FilamentStaticPages\Support\BlockDataPaths;
use Zoker\FilamentStaticPages\Tests\TestCase;

class BlockDataPathsTest extends TestCase
{
    public function test_it_resolves_a_plain_key(): void
    {
        expect(BlockDataPaths::expand(['a' => 'x'], 'a'))->toBe(['a']);
    }

    public function test_it_returns_empty_for_a_missing_key(): void
    {
        expect(BlockDataPaths::expand(['a' => 'x'], 'b'))->toBe([]);
    }

    public function test_it_expands_a_wildcard_over_a_list(): void
    {
        $data = ['items' => [['url' => 'x'], ['url' => 'y']]];

        expect(BlockDataPaths::expand($data, 'items.*.url'))->toBe(['items.0.url', 'items.1.url']);
    }

    public function test_it_expands_a_wildcard_over_an_associative_array(): void
    {
        $data = ['m' => ['en' => ['t' => 1], 'ru' => ['t' => 2]]];

        expect(BlockDataPaths::expand($data, 'm.*.t'))->toBe(['m.en.t', 'm.ru.t']);
    }

    public function test_it_returns_empty_when_wildcard_hits_a_scalar(): void
    {
        expect(BlockDataPaths::expand(['a' => 5], 'a.*.b'))->toBe([]);
    }

    public function test_it_expands_nested_double_wildcards(): void
    {
        $data = ['a' => [['b' => [['c' => 1], ['c' => 2]]]]];

        expect(BlockDataPaths::expand($data, 'a.*.b.*.c'))->toBe(['a.0.b.0.c', 'a.0.b.1.c']);
    }
}
