<?php

namespace Zoker\FilamentStaticPages\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $code
 * @property array<array<string, mixed>> $items
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Menu extends Model
{
    use HasTranslations;

    /**
     * @var array<string>
     */
    public array $translatable = ['items'];

    const string CACHE_KEY = 'fsp-menu';

    protected $casts = [
        'items' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function (Menu $menu) {
            cache()->forget(self::getCacheKey($menu->code));
        });
    }

    public static function getMenu(string $code): self
    {
        if (cache()->has(self::getCacheKey($code))) {

            return self::hydrate([cache()->get(self::getCacheKey($code))])->first();
        }

        $menu = self::where('code', $code)->first();
        if ($menu) {
            cache()->put(self::getCacheKey($code), $menu->getAttributes(), now()->addHour());
        }

        return $menu ?? new self;
    }

    public function getTable(): string
    {
        return config('fsp.table_prefix') . 'menus';
    }

    public static function getCacheKey(string $code): string
    {
        return self::CACHE_KEY . ':' . strtolower($code);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function getUrl(array $item): ?string
    {
        $urlSettings = $item['url'][0] ?? $item['url'];

        return match ($urlSettings['type']) {
            'fsp' => multisite_route('fsp.page', ['page' => $urlSettings['data']['page']]),
            'route' => multisite_route($urlSettings['data']['route'], self::getParamsForRoute($urlSettings)),
            default => $urlSettings['data']['url'],
        };
    }

    /**
     * @param  array<string, array<string, mixed>>  $urlSettings
     * @return array<string, mixed>
     */
    public static function getParamsForRoute(array $urlSettings): array
    {
        $params = [];
        if (isset($urlSettings['data']['params'][0])) {
            $params = array_merge(...$urlSettings['data']['params']);
        }

        return $params;
    }
}
