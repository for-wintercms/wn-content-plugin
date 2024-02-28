<?php

namespace ForWinterCms\Content\Classes;

use Lang;
use Exception;
use ForWinterCms\Content\Models\Page as PageModel;
use ForWinterCms\Content\Classes\Traits\ContentItemsData;
use ForWinterCms\Content\Classes\Interfaces\ContentItems as InterfaceContentItems;

/**
 * ContentItems class
 *
 * @package ForWinterCms\Content\Classes
 */
class ContentItems implements InterfaceContentItems
{
    use \Winter\Storm\Support\Traits\Singleton;
    use Traits\ContentItemsParse;
    use ContentItemsData;

    protected function init()
    {
        try {
            $this->parseContentItems();
        }
        catch (Exception $e){}
    }

    /**
     * Check page slug
     *
     * @param string $pageSlug
     * @return bool
     */
    public function checkPageSlug(string $pageSlug): bool
    {
        return ($pageSlug && isset($this->contentItemList[$pageSlug]));
    }

    public function getItemsList(string $pageSlug)
    {
        return ($this->checkPageSlug($pageSlug)) ? $this->contentItemList[$pageSlug] : [];
    }

    public function getPartials(string $pageSlug, array $itemSlugs = null)
    {
        if ((! $this->checkPageSlug($pageSlug)))
            return [];
        $result = [];
        $isSlugs = !empty($itemSlugs);
        foreach ($this->contentItemList[$pageSlug] as $k => $v)
        {
            if ($isSlugs && ! in_array($k, $itemSlugs))
                continue;
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

    /**
     * Get Item data
     *
     * @param string $pageSlug   Page slug
     * @param string $itemSlug   Item slug (key)
     * @param string|null $lang  Translation language
     *
     * @return string|array|null
     */
    public function getItemData(string $pageSlug, string $itemSlug, string $lang = null)
    {
        return $this->getItemsData($pageSlug, $itemSlug, $lang)[$itemSlug] ?? null;
    }

    /**
     * Get Items data
     *
     * @param string      $pageSlug   Page slug
     * @param array|string|null  $itemSlugs  Item slugs (keys)
     * @param string|null $lang       Translation language
     *
     * @return array
     * @throws
     */
    public function getItemsData(string $pageSlug, array|string $itemSlugs = null, string $lang = null): array
    {
        if (! $this->checkPageSlug($pageSlug))
            return [];

        $page = PageModel::slug($pageSlug)->first();
        if (! $page)
            return [];

        $result = [];
        $fnItems = function() use ($page, $itemSlugs, $lang) {
            if (empty($itemSlugs))
                return $page->items()->itemsLang($lang)->get();
            else if (is_string($itemSlugs))
                return $page->items()->where('name', $itemSlugs)->itemsLang($lang)->get();
            else
                return $page->items()->whereIn('name', $itemSlugs)->itemsLang($lang)->get();
        };

        foreach ($fnItems() as $item)
            $result[$item->name] = $this->getContentItemsData($item);

        return $result;
    }
}
