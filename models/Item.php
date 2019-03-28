<?php

namespace Wbry\Content\Models;

use Model;

/**
 * Item model
 *
 * @package Wbry\Content\Models
 * @author Diamond Systems
 */
class Item extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    public $timestamps = false;

    public $table = 'wbry_content_items';

    protected $jsonable = ['items'];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'page' => 'required|between:1,256',
        'name' => 'required|between:1,256|alpha_dash',
    ];

    public $attributeNames = [
        'name' => 'wbry.content::repeater.items.name_label',
    ];
}
