<?php

namespace ForWinterCms\Content\Models;

use Model;
use Winter\Storm\Database\Builder;
use ForWinterCms\Content\Classes\IconList;

/**
 * Page Model
 * @package ForWinterCms\Content\Models
 */
class Page extends Model
{
    public $table = 'forwn_content_pages';
    public $fillable = ['title','slug','icon','order'];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'slug' => 'required|between:1,256',
    ];

    public $hasMany = [
        'items' => 'ForWinterCms\Content\Models\Item'
    ];

    /*
     * Helpers
     */

    public static function getId(string $page)
    {
        return self::where('slug', $page)->value('id');
    }

    public function getIconListDropDown()
    {
        $iconList = [];
        foreach (IconList::getList() as $itemV)
            $iconList['icon-'.$itemV] = $itemV;
        return $iconList;
    }

    /*
     * Scopes
     */

    public function scopeSlug(Builder $query, string $page)
    {
        $query->where('slug', $page);
    }
}
