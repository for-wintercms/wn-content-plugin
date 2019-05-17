<?php

namespace Wbry\Content\Models;

use Model;

/**
 * Page Model
 * @package Wbry\Content\Models
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
class Page extends Model
{
    public $table = 'wbry_content_pages';
    public $fillable = ['slug'];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'slug' => 'required|between:1,256',
    ];

    /*
     * Helpers
     */

    public static function getId(string $page)
    {
        return self::where('slug', $page)->value('id');
    }

    /*
     * Scopes
     */

    public function scopeSlug(Builder $query, string $page)
    {
        $query->where('slug', $page);
    }
}
