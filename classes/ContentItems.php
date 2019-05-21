<?php

namespace Wbry\Content\Classes;

use Lang;
use Exception;
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

    public function checkPageSlug(string $pageSlug)
    {
        return ($pageSlug && isset($this->contentItemList[$pageSlug]));
    }

    public function getItemsList(string $pageSlug)
    {
        return ($this->checkPageSlug($pageSlug)) ? $this->contentItemList[$pageSlug] : [];
    }

    public function getPartials(string $pageSlug)
    {
        if ((! $this->checkPageSlug($pageSlug)))
            return [];
        $result = [];
        foreach ($this->contentItemList[$pageSlug] as $k => $v)
        {
            if (empty($v['section']) || ! is_string($v['section']))
                continue;

            $partial = $this->contentItemSectionsList[$v['section']]['partial'] ?? null;
            if (! is_string($partial))
                continue;

            $partial = preg_replace("/\.htm$/i", '', $partial) .'.htm';
            if (file_exists($this->contentItemsContentPath.'/'. $partial))
                $result[$k] = $partial;
        }
        return $result;
    }

    public function getPartialPath(string $partial)
    {
        return $this->contentItemsContentPath.'/'. $partial;
    }
}
