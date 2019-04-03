<?php

namespace Wbry\Content\Classes;

use Lang;
use Wbry\Content\Models\Item as ItemModel;
/**
 * Repeater class
 *
 * @package Wbry\Content\Classes
 */
class Repeater
{
    use \October\Rain\Support\Traits\Singleton;
    use Traits\RepeaterParse;

    protected function init()
    {
        $this->parseRepeatersConfig();
    }

    public function getRepeater(string $page, string $name)
    {
        if (! $page || ! $name || ! isset($this->repeaterAllList[$page]))
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
        elseif (! $repeater || ! isset($this->repeaterAllList[$page][$repeater]))
            return [];

        $record = ItemModel::create(['page' => $page, 'name' => $name, 'repeater' => $repeater, 'items' => $content]);

        return $record->items ?? [];
    }
}
