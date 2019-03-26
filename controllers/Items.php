<?php

namespace Wbry\Content\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

/**
 * Items controller
 *
 * @package Wbry\Content\Controllers
 * @author Diamond Systems
 */
class Items extends Controller
{
    public $implement = [    ];
    
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Wbry.Content', 'items');
    }
}
