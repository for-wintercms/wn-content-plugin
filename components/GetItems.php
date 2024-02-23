<?php

namespace ForWinterCms\Content\Components;

use Cms\Classes\ComponentBase;
use ForWinterCms\Content\Models\Page as PageModel;
use ForWinterCms\Content\Classes\ContentItems;
use ForWinterCms\Content\Classes\Traits\ContentItemsData;

/**
 * GetItems component
 *
 * @package ForWinterCms\Content\Components
 */
class GetItems extends ComponentBase
{
    use ContentItemsData;

    public function componentDetails()
    {
        return [
            'name'        => 'forwintercms.content::lang.components.get_items.name',
            'description' => 'forwintercms.content::lang.components.get_items.desc',
        ];
    }

    /**
     * Get Item
     *
     * @param string $pageSlug   Page slug
     * @param string $itemSlug   Item slug (key)
     * @param bool   $isContent  If true return content else item data array
     * @param string|null $lang  Translation language
     *
     * @return string|array
     */
    public function item(string $pageSlug, string $itemSlug, bool $isContent = false, string $lang = null)
    {
        return $this->items($pageSlug, [$itemSlug], $isContent, $lang)[$itemSlug] ?? null;
    }

    /**
     * Get Items list
     *
     * @param string      $pageSlug   Page slug
     * @param array|null  $itemSlugs  Item slugs (keys)
     * @param bool        $isContent  If true return contents list else items data array
     * @param string|null $lang       Translation language
     *
     * @return array
     * @throws
     */
    public function items(string $pageSlug, array $itemSlugs = null, bool $isContent = false, string $lang = null): array
    {
        $contentItems = ContentItems::instance();
        if ((! $contentItems->checkPageSlug($pageSlug)))
            return [];

        $page = PageModel::slug($pageSlug)->first();
        if (! $page)
            return [];

        $result = [];
        $fnItems = function() use ($page, $itemSlugs, $lang) {
            if (empty($itemSlugs))
                return $page->items()->itemsLang($lang)->get();
            else
                return $page->items()->whereIn('name', $itemSlugs)->itemsLang($lang)->get();
        };

        if ($isContent)
        {
            $partials = $contentItems->getPartials($pageSlug, $itemSlugs);
            if (! is_array($partials) || ! count($partials))
                return [];

            $twig = $this->controller->getTwig();
            foreach ($fnItems() as $item)
            {
                if (! isset($partials[$item->name]))
                    continue;
                $result[$item->name] = $twig
                    ->loadTemplate($partials[$item->name])
                    ->render(array_merge($this->controller->vars, $this->getProperties(), [
                        'item_slug'    => $item->name,
                        'partial'      => $partials[$item->name],
                        'content_data' => $this->getContentItemsData($item),
                    ]));
            }
        }
        else
        {
            foreach ($fnItems() as $item)
                $result[$item->name] = $this->getContentItemsData($item);
        }

        return $result;
    }
}
