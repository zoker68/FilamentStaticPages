<?php

namespace Zoker\FilamentStaticPages\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Zoker\FilamentMultisite\Traits\HasMultisite;
use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;
use Zoker\FilamentStaticPages\Classes\Layout;
use Zoker\FilamentStaticPages\Observers\PageObserver;

/**
 * @property int $id
 * @property int $site_id
 * @property string $name
 * @property string $url
 * @property string $layout
 * @property bool $published
 * @property int $parent_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ?self $parent
 */
#[ObservedBy(PageObserver::class)]
class Page extends Model
{
    use HasMultisite;

    const string CACHE_KEY_ROUTES = 'filament_static_pages_routes';

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

    public function parent(): BelongsTo // @phpstan-ignore-line
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function getTable(): string
    {
        return config('filament-static-pages.table_prefix') . 'pages';
    }

    /** @return array<string> */
    public static function getAllRoutes(?int $siteId = null): array
    {
        if (! Schema::hasTable((new self)->getTable())) {
            return [];
        }

        return cache()->rememberForever(
            self::CACHE_KEY_ROUTES,
            fn () => self::allSites()->with('site')->published()->get()->keyBy('url')->toArray()
        );
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUrl(Builder $query, string $url): Builder
    {
        if (empty($url)) {
            return $query->whereNull('url');
        }

        return $query->where('url', $url);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
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
