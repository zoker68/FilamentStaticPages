<?php

namespace Zoker\FilamentStaticPages\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    const string CACHE_KEY = 'filament-static-pages-menu';

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
        return cache()->remember(
            self::getCacheKey($code),
            now()->addMinutes(30),
            function () use ($code) {
                return self::where('code', $code)->first();
            }
        ) ?? new self;
    }

    public function getTable(): string
    {
        return config('filament-static-pages.table_prefix') . 'menus';
    }

    public static function getCacheKey(string $code): string
    {
        return self::CACHE_KEY . ':' . strtolower($code);
    }

    public function getUrl(array $item): ?string
    {
        if (! isset($item['url'][0])) {
            return null;
        }

        $urlSettings = $item['url'][0];

        return match ($urlSettings['type']) {
            'fsp' => route('fsp.' . $urlSettings['data']['page']),
            'route' => route($urlSettings['data']['route'], self::getParamsForRoute($urlSettings)),
            default => $urlSettings['data']['url'],
        };
    }

    public static function getParamsForRoute(array $urlSettings): array
    {
        $params = [];
        if (isset($urlSettings['data']['params'][0])) {
            $params = array_merge(...$urlSettings['data']['params']);
        }

        return $params;
    }
}
