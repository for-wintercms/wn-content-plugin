<?php

namespace ForWinterCms\Content\Components;

use Cms\Classes\ComponentBase;
use ForWinterCms\Content\Classes\ContentItems;
use ForWinterCms\Content\Models\Page as PageModel;

/**
 * GetItems component
 *
 * @package ForWinterCms\Content\Components
 */
class GetItems extends ComponentBase
{
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
     * @param string $pageSlug   - Page slug
     * @param string $itemSlug   - Item slug (key)
     * @param bool   $isContent  - if true return content else item data array
     *
     * @return string|array
     */
    public function item(string $pageSlug, string $itemSlug, bool $isContent = false)
    {
        return $this->items($pageSlug, [$itemSlug], $isContent)[$itemSlug] ?? null;
    }

    /**
     * Get Items list
     *
     * @param string      $pageSlug   - Page slug
     * @param array|null  $itemSlugs  - Item slugs (keys)
     * @param bool        $isContent  - if true return contents list else items data array
     *
     * @return array
     * @throws
     */
    public function items(string $pageSlug, array $itemSlugs = null, bool $isContent = false)
    {
        $contentItems = ContentItems::instance();
        if ((! $contentItems->checkPageSlug($pageSlug)))
            return [];

        $page = PageModel::slug($pageSlug)->first();
        if (! $page)
            return [];

        $result = [];
        $fnItems = function() use ($page, $itemSlugs) {
            if (empty($itemSlugs))
                return $page->items()->get();
            else
                return $page->items()->whereIn('name', $itemSlugs)->get();
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
                        'content_data' => $item->items,
                    ]));
            }
        }
        else
        {
            foreach ($fnItems() as $item)
                $result[$item->name] = $item->items;
        }

        return $result;
    }
}
