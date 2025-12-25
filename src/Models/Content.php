<?php

namespace Zoker\FilamentStaticPages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property string $code
 * @property array<array<string, mixed>> $content
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Content extends Model
{
    use HasTranslations;

    /**
     * @var array<string>
     */
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
        return config('fsp.table_prefix') . 'content';
    }
}
