<?php

namespace ForWinterCms\Content\Classes\Traits;

use Event;
use ForWinterCms\Content\Classes\ContentItems;
use ForWinterCms\Content\Models\Item;

trait ContentItemsData
{
    public function getContentItemsData(Item $itemModel)
    {
        $itemsData = Event::fire('forwintercms.content.contentitemsdata', [$itemModel], true);
        if ($itemsData && is_array($itemsData))
            return $itemsData;

        $contentItems = ContentItems::instance();
        $relationFields = array_diff(
            $contentItems->getContentItemIncludeFields($itemModel->page, $itemModel->name),
            array_keys($itemModel->items??[])
        );

        $relationData = [];
        foreach ($relationFields as $relationField)
        {
            if ($itemModel->hasRelation($relationField))
                $relationData[$relationField] = $itemModel->$relationField;
        }

        return ($itemModel->items??[]) + $relationData;
    }
}
