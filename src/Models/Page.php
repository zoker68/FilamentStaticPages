<?php

namespace Zoker\FilamentStaticPages\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Zoker\FilamentStaticPages\Classes\Layout;

class Page extends Model
{
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
