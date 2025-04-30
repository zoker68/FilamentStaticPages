<?php

namespace Zoker\FilamentStaticPages\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
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
