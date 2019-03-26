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

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wbry_content_items';

    /**
     * @var array Validation rules
     */
    public $rules = [];

    protected $jsonable = ['items'];
}
