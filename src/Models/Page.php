<?php

namespace Zoker\FilamentStaticPages\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'zoker_pages_pages';

    protected $fillable = [
        'name',
        'url',
        'layout',
    ];
}
