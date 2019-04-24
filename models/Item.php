<?php

namespace Wbry\Content\Models;

use Model;
use October\Rain\Database\Builder;

/**
 * Item model
 *
 * @package Wbry\Content\Models
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
class Item extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $implement = [
        'RainLab\Translate\Behaviors\TranslatableModel'
    ];

    public $table = 'wbry_content_items';

    protected $jsonable = ['items'];

    public $translatable = ['items'];

    public $fillable = ['page', 'name', 'items'];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'page' => 'required|between:1,256',
        'name' => 'required|between:1,256|alpha_dash',
    ];

    public $attributeNames = [
        'name' => 'wbry.content::content.items.name_label',
    ];

    /*
     * Scopes
     */

    public function scopePage(Builder $query, string $page)
    {
        $query->where('page', $page);
    }

    public function scopeItem(Builder $query, string $page, string $name)
    {
        $query->where('page', $page)->where('name', $name);
    }
}
