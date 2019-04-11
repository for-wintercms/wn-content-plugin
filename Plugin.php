<?php

namespace Wbry\Content;

use System\Classes\PluginBase;
use Wbry\Content\Classes\ContentItem;

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
                'repeater' => [ContentItem::instance(), 'filterRepeater'],
            ],
            'functions' => [
                'repeater' => [ContentItem::instance(), 'getRepeater'],
            ],
        ];
    }
}
