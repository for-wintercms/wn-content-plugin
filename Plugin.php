<?php

namespace Wbry\Content;

use System\Classes\PluginBase;
use Wbry\Content\Classes\ContentItems;

/**
 * Plugin - Content control
 *
 * @package Wbry\Content
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
class Plugin extends PluginBase
{
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'contentItem' => [ContentItems::instance(), 'filterRepeater'],
            ],
            'functions' => [
                'contentItem' => [ContentItems::instance(), 'getRepeater'],
            ],
        ];
    }

    public function registerComponents()
    {
        return [
            'Wbry\Content\Components\GenerateContent' => 'demoTodo'
        ];
    }
}
