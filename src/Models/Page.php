<?php

namespace Zoker\FilamentStaticPages\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Zoker\FilamentStaticPages\Classes\Layout;
use Zoker\FilamentStaticPages\Observers\PageObserver;

#[ObservedBy(PageObserver::class)]
class Page extends Model
{
    const string CACHE_KEY_ROUTES = 'filament_static_pages_routes';

    protected $table = 'zoker_pages_pages';

    protected $casts = [
        'published' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'url',
        'layout',
        'published',
    ];

    public static function getAllRoutes(): array
    {
        return cache()->remember(self::CACHE_KEY_ROUTES, now()->addMinutes(10), fn () => self::published()->pluck('url')->toArray());
    }

    public function blocks(): MorphMany
    {
        return $this->morphMany(Block::class, 'blockable');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function getLayoutComponent(): string
    {
        return Layout::getLayoutComponent($this->layout);
    }
}
