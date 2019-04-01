<?php

namespace Wbry\Content;

use System\Classes\PluginBase;
use Wbry\Content\Classes\Repeater;

/**
 * Plugin - Content control
 *
 * @package Wbry\Content
 * @author Diamond Systems
 */
class Plugin extends PluginBase
{
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'repeater' => [Repeater::instance(), 'filterRepeater'],
            ],
            'functions' => [
                'repeater' => [Repeater::instance(), 'getRepeater'],
            ],
        ];
    }
}
