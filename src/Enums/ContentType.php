<?php

namespace Zoker\FilamentStaticPages\Enums;

enum ContentType: string
{
    case Page = 'page';
    case Content = 'content';

    public function getLabel(): string
    {
        return match ($this) {
            self::Page => __('fsp::lang.system.page'),
            self::Content => __('fsp::lang.system.content'),
        };
    }
}
