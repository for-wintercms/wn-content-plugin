<?php

namespace Wbry\Content;

use System\Classes\PluginBase;

/**
 * Plugin - Content control
 *
 * @package Wbry\Content
 * @author Diamond Systems
 */
class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Wbry\Content\Components\Repeater' => 'repeater'
        ];
    }

    public function registerSettings()
    {}
}
