<?php

namespace Zoker\FilamentStaticPages\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Content extends Model
{
    use HasTranslations;

    public array $translatable = ['content'];

    protected $casts = [
        'content' => 'array',
    ];

    protected $fillable = [
        'code',
        'content',
    ];

    public function getTable(): string
    {
        return config('filament-static-pages.table_prefix') . 'content';
    }
}
