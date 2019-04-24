<?php

namespace Wbry\Content\Classes;

use Lang;
use Exception;
use Wbry\Content\Models\Item as ItemModel;
use Wbry\Content\Classes\Interfaces\ContentItems as InterfaceContentItems;

/**
 * ContentItems class
 *
 * @package Wbry\Content\Classes
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
class ContentItems implements InterfaceContentItems
{
    use \October\Rain\Support\Traits\Singleton;
    use Traits\ContentItemsParse;

    protected function init()
    {
        try {
            $this->parseContentItems();
        }
        catch (Exception $e){}
    }

    public function getRepeater(string $page, string $name)
    {
        if (! $page || ! $name || ! isset($this->contentItemList[$page]))
            return [];

        $record = ItemModel::item($page, $name)->first();

        return $record->items ?? [];
    }

    public function filterRepeater($content, array $params = [])
    {
        # get
        $page = $params['page'] ?? null;
        $name = $params['name'] ?? null;

        if (! $page || ! $name || ! is_string($page) || ! is_string($name))
            return [];

        $page  = camel_case($page);
        $items = $this->getRepeater($page, $name);
        if ($items)
            return $items;

        # create
        $repeater = $params['repeater'] ?? $name;
        $generate = $params['generate'] ?? true;

        if (! $repeater || ! is_string($repeater))
            return [];

        if ($generate) {
            $content = $this->generateRepeaterConfig($content, $page, $repeater);
            if ($content === false)
                return [];
        }
        elseif (! $repeater || ! isset($this->contentItemList[$page][$repeater]))
            return [];

        $record = ItemModel::create(['page' => $page, 'name' => $name, 'repeater' => $repeater, 'items' => $content]);

        return $record->items ?? [];
    }
}
