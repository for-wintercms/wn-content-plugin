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
            if ($partial = $this->getSectionPartialPath($v['section']))
                $result[$k] = $partial;
        }
        return $result;
    }

    public function getSectionPartialPath(string $section)
    {
        $partial = $this->contentItemSectionsList[$section]['partial'] ?? null;
        if (! is_string($partial))
            return null;
        $partial = $this->contentItemsContentPath .'/'. preg_replace("/\.htm$/i", '', $partial) .'.htm';
        return file_exists($partial) ? $partial : null;
    }
}
