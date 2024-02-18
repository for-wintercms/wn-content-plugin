<?php

namespace ForWinterCms\Content\Classes\Traits;

use ForWinterCms\Content\Classes\ContentItems;
use ForWinterCms\Content\Models\Item;

trait ContentItemsData
{
    public function getContentItemsData(Item $itemModel)
    {
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
