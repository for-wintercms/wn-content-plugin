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
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'wbry_content_items';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
