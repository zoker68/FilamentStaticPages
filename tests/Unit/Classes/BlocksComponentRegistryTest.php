<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Classes;

use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;
use Zoker\FilamentStaticPages\Tests\TestCase;
use Zoker\FilamentStaticPages\View\Components\ContentBlock;
use Zoker\FilamentStaticPages\View\Components\HeadingBlock;
use Zoker\FilamentStaticPages\View\Components\QuestionAnswerBlock;

class BlocksComponentRegistryTest extends TestCase
{
    /** @var array<string, string> */
    private array $original = [];

    protected function setUp(): void
    {
        parent::setUp();

        // The registry stores components in a static array; snapshot it for isolation.
        $this->original = BlocksComponentRegistry::$components;
    }

    protected function tearDown(): void
    {
        BlocksComponentRegistry::$components = $this->original;

        parent::tearDown();
    }

    public function test_it_knows_about_built_in_blocks(): void
    {
        $this->assertTrue(BlocksComponentRegistry::has('Content'));
        $this->assertFalse(BlocksComponentRegistry::has('Nope'));
    }

    public function test_get_returns_the_component_class_or_null(): void
    {
        $this->assertEquals(ContentBlock::class, BlocksComponentRegistry::get('Content'));
        $this->assertEquals(ContentBlock::class, BlocksComponentRegistry::getComponent('Content'));
        $this->assertNull(BlocksComponentRegistry::get('Nope'));
    }

    public function test_get_components_returns_the_full_map(): void
    {
        $components = BlocksComponentRegistry::getComponents();

        $this->assertArrayHasKey('Content', $components);
        $this->assertArrayHasKey('Banner', $components);
    }

    public function test_register_adds_a_component_with_explicit_name(): void
    {
        BlocksComponentRegistry::register(ContentBlock::class, 'CustomBlock');

        $this->assertTrue(BlocksComponentRegistry::has('CustomBlock'));
        $this->assertEquals(ContentBlock::class, BlocksComponentRegistry::get('CustomBlock'));
    }

    public function test_register_defaults_the_name_to_the_class_basename(): void
    {
        BlocksComponentRegistry::register(ContentBlock::class);

        $this->assertTrue(BlocksComponentRegistry::has('ContentBlock'));
    }

    public function test_every_registered_block_declares_translatable_fields(): void
    {
        // Guards against a new block silently dropping out of translation.
        foreach (BlocksComponentRegistry::getComponents() as $name => $class) {
            $this->assertTrue(
                property_exists($class, 'translatable'),
                "Block [{$name}] ({$class}) must declare a static \$translatable property."
            );
            $this->assertIsArray($class::$translatable, "Block [{$name}] \$translatable must be an array.");
        }
    }

    public function test_built_in_blocks_translatable_declarations(): void
    {
        $this->assertSame(['heading'], HeadingBlock::$translatable);
        $this->assertSame(['content'], ContentBlock::$translatable);
        $this->assertSame([
            'categories.*.title',
            'categories.*.questions.*.question',
            'categories.*.questions.*.answer',
        ], QuestionAnswerBlock::$translatable);
    }
}
