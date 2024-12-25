<?php

namespace Zoker\FilamentStaticPages\Models;

use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;
use Zoker\FilamentStaticPages\Classes\Layout;
use Zoker\FilamentStaticPages\Observers\PageObserver;

#[ObservedBy(PageObserver::class)]
class Page extends Model
{
    const string CACHE_KEY_ROUTES = 'filament_static_pages_routes';

    protected $table = 'zoker_pages_pages';

    protected $casts = [
        'published' => 'boolean',
        'content' => 'array',
    ];

    protected $fillable = [
        'name',
        'url',
        'layout',
        'published',
    ];

    public function getTable(): string
    {
        return config('filament-static-pages.table_prefix') . 'pages';
    }

    public static function getAllRoutes(): array
    {
        if (! Schema::hasTable((new self)->getTable())) {
            return [];
        }

        return cache()->remember(self::CACHE_KEY_ROUTES, now()->addMinutes(10), fn () => self::published()->pluck('url')->toArray());
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function getLayoutComponent(): string
    {
        return Layout::getLayoutComponent($this->layout);
    }

    public function getBlockViewComponent(string $type): string
    {
        if (! BlocksComponentRegistry::has($type)) {
            throw new InvalidArgumentException('Unknown component: ' . $type);
        }

        $componentClass = BlocksComponentRegistry::getComponent($type);

        return $componentClass::getViewComponent();
    }
}
