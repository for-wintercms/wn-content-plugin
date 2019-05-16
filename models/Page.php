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
}
