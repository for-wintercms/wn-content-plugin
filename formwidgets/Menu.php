<?php
namespace  ForWinterCms\Content\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * Content Menu widget
 *
 * @link https://wintercms.com/docs/v1.2/docs/backend/widgets#form-widgets
 */
class Menu extends FormWidgetBase
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'contentMenu';

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('menu');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->vars['field'] = $this->formField;
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addCss(['less/content-menu.less']);
        $this->addJs(['js/content-menu.js']);
    }
}