<?php

namespace Littleboy130491\Sumimasen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Littleboy130491\Sumimasen\Traits\HasSections;
use Spatie\Translatable\HasTranslations;

class Component extends Model
{
    use HasSections, HasTranslations, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'section',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'section' => 'array',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'section',
    ];
}
