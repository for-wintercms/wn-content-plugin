<?php

namespace Wbry\Content;

use Event;
use Backend;
use System\Classes\PluginBase;
use Wbry\Content\Classes\ContentItems;
use Wbry\Content\Models\Item as ItemModel;

/**
 * Plugin - Content control
 *
 * @package Wbry\Content
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'   => 'wbry.content::lang.plugin.name',
            'description' => 'wbry.content::lang.plugin.description',
            'author' => 'Weberry, Diamond',
            'icon'   => 'oc-icon-list-alt',
        ];
    }

    public function registerPermissions()
    {
        return [
            'wbry.content.items' => [
                'label' => 'wbry.content::lang.plugin.name',
                'tab'   => 'wbry.content::lang.permissions.items',
            ],
            'wbry.content.items_changes' => [
                'label' => 'wbry.content::lang.plugin.name',
                'tab'   => 'wbry.content::lang.permissions.items_changes',
            ],
        ];
    }

    public function registerNavigation()
    {
        $menuItems = [
            'label'   => 'wbry.content::lang.menu.items',
            'icon'    => 'icon-list-alt',
            'iconSvg' => 'plugins/wbry/content/assets/images/icon-content.svg',
        ];
        Event::fire('wbry.content.menu.items', [&$menuItems]);
        if (! is_array($menuItems))
            return [];

        return [
            'items' => array_merge($menuItems, [
                'url'         => Backend::url('wbry/content/items'),
                'permissions' => ['wbry.content.items'],
            ])
        ];
    }

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
            'Wbry\Content\Components\GetContent' => 'getContent'
        ];
    }

    public function boot()
    {
        # extend models
        # ================
        ItemModel::extend(function($model)
        {
            # add RainLab Translatable Model
            # =================================
            $transModel = 'RainLab\Translate\Behaviors\TranslatableModel';
            if (class_exists($transModel))
                $model->implement[] = $transModel;
        });
    }
}
