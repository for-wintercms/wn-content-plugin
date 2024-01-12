<?php

namespace ForWinterCms\Content;

use App;
use Event;
use Backend;
use System\Classes\PluginBase;

/**
 * Plugin - Content control
 *
 * @package ForWinterCms\Content
 */
class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'   => 'forwintercms.content::lang.plugin.name',
            'description' => 'forwintercms.content::lang.plugin.description',
            'author' => 'ForWinterCms',
            'icon'   => 'oc-icon-list-alt',
        ];
    }

    public function registerPermissions()
    {
        return [
            'forwintercms.content.items' => [
                'label' => 'forwintercms.content::lang.plugin.name',
                'tab'   => 'forwintercms.content::lang.permissions.items',
            ],
            'forwintercms.content.items_changes' => [
                'label' => 'forwintercms.content::lang.plugin.name',
                'tab'   => 'forwintercms.content::lang.permissions.items_changes',
            ],
        ];
    }

    public function registerNavigation()
    {
        $menuItems = [
            'label'   => 'forwintercms.content::lang.menu.items',
            'icon'    => 'icon-list-alt',
            'iconSvg' => 'plugins/forwintercms/content/assets/images/icon-content.svg',
        ];
        Event::fire('forwintercms.content.menu.items', [&$menuItems]);
        if (! is_array($menuItems))
            return [];

        return [
            'items' => array_merge($menuItems, [
                'url'         => Backend::url('forwintercms/content/items'),
                'permissions' => ['forwintercms.content.items'],
            ])
        ];
    }

    public function registerComponents()
    {
        return [
            'ForWinterCms\Content\Components\GetContent' => 'getContent',
            'ForWinterCms\Content\Components\GetItems'   => 'getItems',
        ];
    }

    public function boot()
    {
        # add Winter.Translate plugin
        # =============================
        $WtLocaleModel = 'Winter\Translate\Models\Locale';
        if (class_exists($WtLocaleModel))
        {
            Event::listen('forwintercms.content.defaultLocale', function($defaultLocale) use($WtLocaleModel) {
                return $WtLocaleModel::getDefault()->code ?? App::getLocale();
            });
            Event::listen('forwintercms.content.locales', function($locales) use($WtLocaleModel) {
                return $WtLocaleModel::listEnabled();
            });
        }
    }
}
