<?php

namespace Zoker\FilamentStaticPages\Models;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Zoker\FilamentMultisite\Traits\HasMultisite;
use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;
use Zoker\FilamentStaticPages\Classes\Layout;
use Zoker\FilamentStaticPages\Observers\PageObserver;

/**
 * @property int $id
 * @property int $site_id
 * @property int $parent_id
 * @property ?int $original_id
 * @property string $name
 * @property string $url
 * @property string $layout
 * @property array<array<string, mixed>> $content
 * @property bool $published
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ?self $parent
 */
#[ObservedBy(PageObserver::class)]
class Page extends Model
{
    use HasMultisite;

    const string CACHE_KEY_ROUTES = 'filament_static_pages_routes';

    const string CACHE_KEY_ALLOWED_URLS = 'filament_static_pages_allowed_urls';

    protected $casts = [
        'published' => 'boolean',
        'content' => 'array',
    ];

    protected $fillable = [
        'name',
        'url',
        'layout',
        'published',
        'original_id',
    ];

    public function parent(): BelongsTo // @phpstan-ignore-line
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * The original page this one is a translation of (lives on the default site).
     * Cross-site, so the multisite global scope is dropped.
     */
    public function original(): BelongsTo // @phpstan-ignore-line
    {
        return $this->belongsTo(self::class, 'original_id')->withoutGlobalScope('multisite');
    }

    /**
     * Translations of this (original) page on the other sites.
     */
    public function translations(): HasMany // @phpstan-ignore-line
    {
        return $this->hasMany(self::class, 'original_id')->withoutGlobalScope('multisite');
    }

    /**
     * All versions of this page across sites — the original plus its translations —
     * regardless of the current site. Each carries its own site + localized url.
     *
     * @return Collection<int, self>
     */
    public function translationGroup(): Collection
    {
        $rootId = $this->original_id ?? $this->id;

        return static::allSites()
            ->with('site')
            ->where(function (Builder $query) use ($rootId) {
                $query->where('id', $rootId)->orWhere('original_id', $rootId);
            })
            ->get();
    }

    public function getTable(): string
    {
        return config('fsp.table_prefix') . 'pages';
    }

    /** @return array<int, array<string, mixed>> */
    public static function getAllRoutes(): array
    {
        return self::rememberNonEmpty(
            self::CACHE_KEY_ROUTES,
            fn () => self::allSites()->with('site')->published()->get()->toArray()
        );
    }

    /** @return array<int, ?string> */
    public static function getAllowedUrls(): array
    {
        return self::rememberNonEmpty(
            self::CACHE_KEY_ALLOWED_URLS,
            fn () => self::allSites()
                ->published()
                ->pluck('url')
                ->unique()
                ->values()
                ->toArray()
        );
    }

    /**
     * Never caches an empty result: an artisan run against another database shares the store and would pin it for the live site.
     *
     * @param  Closure(): array<mixed>  $resolve
     * @return array<mixed>
     */
    private static function rememberNonEmpty(string $key, Closure $resolve): array
    {
        // An empty array can only be a leftover of the old code: treat it as a miss so it heals itself.
        $cached = cache()->get($key);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        if (! Schema::hasTable((new self)->getTable())) {
            return [];
        }

        $result = $resolve();
        if ($result !== []) {
            cache()->forever($key, $result);
        }

        return $result;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUrl(Builder $query, ?string $url): Builder
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
