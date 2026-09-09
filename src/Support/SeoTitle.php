<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Support;

final class SeoTitle
{
    public static function withSuffix(string $title): string
    {
        return $title . ' | ' . config('fsp.site_name');
    }
}
