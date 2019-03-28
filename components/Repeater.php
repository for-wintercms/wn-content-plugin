<?php

namespace Wbry\Content\Components;

use Lang;
use Cms\Classes\ComponentBase;
use Wbry\Content\Models\Item as ItemModel;

/**
 * Repeater component
 *
 * @package Wbry\Content\Components
 * @author Diamond Systems
 */
class Repeater extends ComponentBase
{
    use \Wbry\Content\Classes\Traits\RepeaterParse;

    protected $errorMsg = null;

    public function componentDetails()
    {
        return [
            'name'        => 'wbry.content::components.repeater.name',
            'description' => 'wbry.content::components.repeater.desc',
        ];
    }

    public function init()
    {
        $this->parseRepeatersConfig();
    }

    public function first($page, $name)
    {
        if (! $this->checkRepeaterPage($page))
        {
            $this->errorMsg = Lang::get('wbry.content::components.repeater.errors.no_page');
            return null;
        }

        if (! $name || ! is_string($name))
            return null;

        $record = ItemModel::where('page', $page)->where('name', $name)->first();

        return $record->items ?? [];
    }

    public function create($page, $name, $repeater, $data)
    {
        if ($this->first($page, $name))
        {
            Lang::get('wbry.content::components.repeater.errors.no_repeater');  // Todo
            return false;
        }

        if ($this->checkRepeaterSlug($page, $repeater))
        {
            $this->errorMsg = Lang::get('wbry.content::components.repeater.errors.no_repeater');
            return false;
        }

        return ItemModel::create(['page' => $page, 'name' => $name, 'repeater' => $repeater, 'items' => $data]);
    }

//    public function firstOrCreate($page, $name, $repeater, $data)
//    {
//        if ($record = $this->first($page, $name))
//            return $record;
//    }

    /*
     * Helpers
     */

    public function checkRepeaterPage($page)
    {
        return ($page && is_string($page) && isset($this->repeaterAllList[$page]));
    }

    public function checkRepeaterSlug($page, $slug)
    {
        if (! $this->checkRepeaterPage($page))
            return false;

        return ($slug && is_string($slug) && isset($this->repeaterAllList[$page][$slug]));
    }

    public function getError()
    {
        return $this->errorMsg;
    }
}
