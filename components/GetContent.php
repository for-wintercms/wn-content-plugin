<?php

namespace Wbry\Content\Components;

use Cms\Classes\ComponentBase;
use Wbry\Content\Classes\ContentItems;
use Wbry\Content\Models\Item as ItemModel;

/**
 * GetContent component
 *
 * @package Wbry\Content\Components
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
class GetContent extends ComponentBase
{
    use \Illuminate\Validation\Concerns\ValidatesAttributes;

    protected $pageSlug = null;
    protected $is404 = true;
    protected $itemData = [];

    /**
     * @var \Wbry\Content\Classes\ContentItems
     */
    protected $contentItem = null;

    public function componentDetails()
    {
        return [
            'name'        => 'wbry.content::lang.components.get_content.name',
            'description' => 'wbry.content::lang.components.get_content.desc',
        ];
    }

    public function defineProperties()
    {
        return [
            'pageSlug' => [
                'title'       => 'wbry.content::lang.components.get_content.page_slug_title',
                'description' => 'wbry.content::lang.components.get_content.page_slug_desc',
                'default'     => "{{ :page_slug }}",
                'type'        => 'string',
            ],
            'is404' => [
                'title'       => 'wbry.content::lang.components.get_content.is404_title',
                'description' => 'wbry.content::lang.components.get_content.is404_desc',
                'default'     => true,
                'type'        => 'checkbox',
                'showExternalParam' => false,
            ],
        ];
    }

    public function init()
    {
        $this->contentItem = ContentItems::instance();
    }

    public function onRun()
    {
        $this->is404 = (bool)$this->property('is404', true);
        $pageSlug = $this->property('pageSlug', $this->param('page_slug'));

        if ($this->validateAlphaDash('pageSlug', $pageSlug) && $this->contentItem->checkPageSlug($pageSlug))
            $this->pageSlug = $pageSlug;

        if (is_null($this->pageSlug) && $this->is404)
            return $this->response404();
    }

    public function getItemData($itemSlug = null)
    {
        if (! $itemSlug || ! is_string($itemSlug) || ! isset($this->itemData[$itemSlug]))
            return [];
        return $this->itemData[$itemSlug];
    }

    public function getSections()
    {
        if (is_null($this->pageSlug))
            return [];

        $partials = $this->contentItem->getPartials($this->pageSlug);
        if (! is_array($partials) || ! count($partials))
            return [];

        $items = ItemModel::page($this->pageSlug)->get();
        if (! $items)
            return [];

        $result = [];
        foreach ($items as $item)
        {
            $this->itemData[$item->name] = $item->items;

            if (! isset($partials[$item->name]))
                continue;

            $result[] = [
                'item_slug'    => $item->name,
                'partial'      => $partials[$item->name],
                'content_data' => $item->items,
            ];
        }
        return $result;
    }

    public function getContent()
    {
        $twig = $this->controller->getTwig();
        $content = '';
        foreach ($this->getSections() as $section)
        {
            $content .= $twig
                ->loadTemplate($section['partial'])
                ->render(array_merge($this->controller->vars, $this->getProperties(), $section));
        }
        return $content;
    }

    public function response404()
    {
        return $this->controller->run('404');
    }
}
