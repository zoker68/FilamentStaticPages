<?php

namespace Zoker\FilamentStaticPages\Classes;

class Layout
{
    /**
     * @return array<string, string>
     */
    public static function getLayoutOptions(): array
    {
        $layouts = glob(resource_path('views/components/layouts/*.blade.php'));
        if (! $layouts) {
            return [];
        }

        $layouts = array_map(function ($layout) {
            $layout = str_replace('.blade.php', '', $layout);

            return pathinfo($layout, PATHINFO_BASENAME);
        }, $layouts);

        return array_combine($layouts, $layouts);
    }

    public static function getLayoutComponent(string $layout): string
    {
        return 'layouts.' . $layout;
    }
}
