<?php

namespace Zoker\FilamentStaticPages\Models;

use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Zoker\FilamentStaticPages\Classes\ComponentRegistry;

class Block extends Model
{
    use SoftDeletes;

    protected $table = 'zoker_pages_blocks';

    protected $casts = [
        'data' => 'array',
        'published' => 'boolean',
    ];

    protected $fillable = [
        'page_id',
        'component',
        'sort',
        'published',
        'data',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        static::saving(function (self $model) {
            if (! $model->data) {
                $model->data = [];
            }
        });
    }

    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getViewComponent(): string
    {
        if (! ComponentRegistry::has($this->component)) {
            throw new InvalidArgumentException('Unknown component: ' . $this->component);
        }

        $componentClass = ComponentRegistry::getComponent($this->component);

        return $componentClass::getViewComponent();
    }
}
