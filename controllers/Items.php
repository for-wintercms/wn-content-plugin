<?php

namespace Wbry\Content\Controllers;

use Db;
use App;
use Lang;
use View;
use Block;
use Event;
use Flash;
use Session;
use Backend;
use Response;
use Validator;
use Exception;
use BackendMenu;
use Backend\Classes\Controller;
use Wbry\Content\Models\Item as ItemModel;
use Wbry\Content\Models\Page as PageModel;
use Wbry\Content\Classes\IconList;
use Wbry\Content\Classes\Interfaces\ContentItems;
use October\Rain\Exception\ValidationException;
use October\Rain\Exception\ApplicationException;

/**
 * Items controller
 *
 * @package Wbry\Content\Controllers
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
class Items extends Controller implements ContentItems
{
    use \Wbry\Content\Classes\Traits\ContentItemsParse;

    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
        'Backend\Behaviors\ReorderController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = ['wbry.content.items'];

    public $page          = null;
    public $locales       = null;
    public $defaultLocale = null;
    public $transLocales  = null;
    public $menuName      = null;
    public $listTitle     = null;
    public $actionAjax    = null;
    public $actionId      = null;
    public $ajaxHandler   = null;

    public $isContentItemError = false;

    /*
     * Initialize
     */

    public function __construct()
    {
        parent::__construct();

        $this->locales();
        $this->translatableDataManager();
        $this->parseContentItemsData();
        $this->addActionMenu();
        $this->addDynamicActionMethods();
        $this->extendItemModel();
        $this->addAssets();
    }

    protected function locales()
    {
        # set lang
        # ==========
        if (Session::has('locale'))
        {
            $locale = Session::get('locale');
            if ($locale !== App::getLocale())
                App::setLocale($locale);
        }
        else
            $locale = App::getLocale();

        # get locale list
        # =================
        $TL_Model = 'RainLab\Translate\Models\Locale';
        if (class_exists($TL_Model))
        {
            $this->defaultLocale = $TL_Model::getDefault()->code ?? null;
            $this->locales = $TL_Model::listEnabled();

            if (! isset($this->locales[$this->defaultLocale]))
                $this->defaultLocale = isset($this->locales[$locale]) ? $locale : null;

            if ($this->defaultLocale && is_array($this->locales) && count($this->locales))
                $this->transLocales = array_diff(array_keys($this->locales), [$this->defaultLocale]);
        }
    }

    protected function translatableDataManager()
    {
        if (! $this->transLocales)
            return;

        ItemModel::extend(function($model)
        {
            $model->bindEvent('model.beforeSetAttribute', function($attr, $data) use ($model)
            {
                if (isset($this->locales[$attr]))
                {
                    $model->addJsonable($attr);
                    $model->setAttributeTranslated('items', $data, $attr);
                }
            });
            $model->bindEvent('model.beforeSave', function() use ($model)
            {
                foreach ($this->locales as $code => $lang)
                    unset($model->attributes[$code]);
            });
        });
    }

    protected function parseContentItemsData()
    {
        try {
            $this->parseContentItems();
        }
        catch (Exception $e) {
            $this->isContentItemError = true;
            $this->handleError($e);
        }
    }

    protected function addActionMenu()
    {
        $this->page = request()->segment(5);
        if (! $this->page)
            $this->page = $this->getContentItemDefaultPage();
        $this->menuName = isset($this->menuList[$this->page]) ? $this->menuList[$this->page]['label'] : '';

        BackendMenu::setContext('Wbry.Content', 'items');
        BackendMenu::setContextSideMenu($this->page);

        Event::listen('backend.menu.extendItems', function($menu)
        {
            # submenu attributes
            # ======================
            $submenu = [];
            if ($this->menuList)
            {
                $submenu      = $this->menuList;
                $isEditPage   = $this->isPageEdit();
                $isEditDelete = $this->isPageDelete();

                foreach ($submenu as &$menuItem)
                {
                    $menuAttr = [
                        'data-submenu-title'  => $menuItem['label'],
                        'data-submenu-slug'   => $menuItem['slug'],
                        'data-submenu-icon'   => $menuItem['icon'] ?? '',
                        'data-submenu-order'  => $menuItem['order'] ?? '',
                        'data-submenu-edit'   => $isEditPage,
                        'data-submenu-delete' => $isEditDelete,
                    ];

                    if (! isset($menuItem['attributes']))
                        $menuItem['attributes'] = $menuAttr;
                    elseif (is_array($menuItem['attributes']))
                        $menuItem['attributes'] = array_merge($menuItem['attributes'], $menuAttr);
                    else
                        $menuItem['attributes'] = $menuAttr[] = $menuItem['attributes'];
                }
            }

            # add new page btn
            # ======================
            if ($this->isPageCreate())
            {
                $submenu['create_new_page'] = [
                    'label' => Lang::get('wbry.content::content.submenu.create_page_btn'),
                    'url' => 'javascript:;',
                    'icon' => 'icon-plus-circle',
                    'order' => -1000,
                    'attributes' => [
                        'data-btn-type' => 'create_new_page',
                    ],
                ];
            }

            # add submenu list
            # ======================
            $menu->addSideMenuItems('Wbry.Content', 'items', $submenu);
        });
    }

    protected function addDynamicActionMethods()
    {
        if (! $this->menuList)
            return;

        $this->addDynamicMethod($this->action, self::class);
        if ($this->ajaxHandler = $this->getAjaxHandler())
        {
            $this->actionAjax = $this->action.'_'.$this->ajaxHandler;
            if (! $this->methodExists($this->actionAjax))
                $this->addDynamicMethod($this->actionAjax, self::class);
        }
    }

    protected function extendItemModel()
    {
        ItemModel::extend(function($model) {
            $model->bindEvent('model.beforeSave', function() use ($model) {
                unset($model->attributes['page_slug']);
            });
            $model->bindEvent('model.afterSave', function() use ($model) {
                $model->attributes['page_slug'] = $this->page;
            });
            $model->bindEvent('model.afterFetch', function() use ($model) {
                $model->attributes['page_slug'] = $this->page;
            });
        });
    }

    protected function addAssets()
    {
        # Custom
        $this->addCss('/plugins/wbry/content/assets/css/backend/main.css');
        $this->addJs('/plugins/wbry/content/assets/js/backend/items_page.js');

        # framework extras
        $this->addJs('/modules/system/assets/js/framework.extras.js');
        $this->addCss('/modules/system/assets/css/framework.extras.css');
    }

    /*
     * Functional limitations
     */

    public function isItemCreate()
    {
        return $this->getEventResult('wbry.content.isItemCreate');
    }

    public function isItemCreateNewTmp()
    {
        return $this->getEventResult('wbry.content.isItemCreateNewTmp');
    }

    public function isItemCreateReadyTmp()
    {
        return $this->getEventResult('wbry.content.isItemCreateReadyTmp');
    }

    public function isItemRename()
    {
        return $this->getEventResult('wbry.content.isItemRename');
    }

    public function isItemRenameTitle()
    {
        return $this->getEventResult('wbry.content.isItemRenameTitle');
    }

    public function isItemRenameSlug()
    {
        return $this->getEventResult('wbry.content.isItemRenameSlug');
    }

    public function isItemDelete()
    {
        return $this->getEventResult('wbry.content.isItemDelete');
    }

    public function isItemSort()
    {
        return $this->getEventResult('wbry.content.isItemSort');
    }

    public function isPageCreate()
    {
        return $this->getEventResult('wbry.content.isPageCreate');
    }

    public function isPageEdit()
    {
        return $this->getEventResult('wbry.content.isPageEdit');
    }

    public function isPageDelete()
    {
        return $this->getEventResult('wbry.content.isPageDelete');
    }

    public function hasAccessItemsChanges()
    {
        static $itemsChanges;
        return $itemsChanges ?: ($itemsChanges = $this->user->hasAccess('wbry.content.items_changes'));
    }

    private function getEventResult($event)
    {
        $default = $this->hasAccessItemsChanges();
        $result  = Event::fire($event, [$this->page, $default], true);

        return $result !== null ? $result : $default;
    }

    /*
     * Helpers
     */

    public function getListTitle($itemSlug, $default = '-')
    {
        if (is_string($itemSlug) && isset($this->contentItemList[$this->page][$itemSlug]))
            return $this->contentItemList[$this->page][$itemSlug]['title'];
        else
            return $default;
    }

    public function getPageUrl()
    {
        return $this->menuList[$this->page]['url'] ?? Backend::url('wbry/content/items');
    }

    public function getListPageTitle()
    {
        return $this->menuName ?: Lang::get('wbry.content::content.list.title');
    }

    public function getReadyItemsList()
    {
        $tmpList = [];

        if (!empty($this->contentItemList[$this->page]))
        {
            $tmpList['ready'] = array_diff_key(
                array_map(function($v){return $v['title'] ?? '';}, $this->contentItemList[$this->page]),
                ItemModel::page($this->page)->lists('id', 'name')
            );
            if (! count($tmpList['ready']))
                unset($tmpList['ready']);
        }

        if (! empty($this->contentItemSectionsList))
            $tmpList['sections'] = array_map(function($v){return $v['title'] ?? '';}, $this->contentItemSectionsList);

        return $tmpList;
    }

    public function getIconList()
    {
        return IconList::getList();
    }

    public function strActive(string $str = null)
    {
        static $saveStr;

        if ($str)
            return $saveStr = $str;
        elseif (! $saveStr)
            return '';
        else
        {
            $str = $saveStr;
            $saveStr = '';
            return $str;
        }
    }

    /*
     * Ajax
     */

    /**
     * @throws
     */
    public function onCreatePage()
    {
        if (! $this->isPageCreate())
            Flash::error(Lang::get('wbry.content::content.errors.non_page_create'));

        $this->buildContentItemPage(post());

        Flash::success(Lang::get('wbry.content::content.success.create_page', ['page' => post('title')]));

        $pageSlug = post('slug');
        if ($pageSlug && ! empty($this->menuList[$pageSlug]))
            return redirect($this->menuList[$pageSlug]['url']);
        else
            return back();
    }

    /**
     * @throws
     */
    public function onEditPage()
    {
        if (! $this->isPageEdit())
            Flash::error(Lang::get('wbry.content::content.errors.non_page_edit'));

        $pageSlug    = post('slug');
        $oldPageSlug = post('old_slug');

        $this->buildContentItemPage(post(), true, $oldPageSlug);

        Flash::success(Lang::get('wbry.content::content.success.edit_page', ['page' => post('title')]));

        if ($this->page == $oldPageSlug && $pageSlug && ! empty($this->menuList[$pageSlug]))
            return redirect($this->menuList[$pageSlug]['url']);
        else
            return back();
    }

    /**
     * @throws
     */
    public function onDeletePage()
    {
        if (! $this->isPageDelete())
            Flash::error(Lang::get('wbry.content::content.errors.non_page_delete'));

        $pageSlug = post('slug');
        $this->deleteContentItemPage($pageSlug);

        Flash::success(Lang::get('wbry.content::content.success.delete_page'));

        if ($this->page == $pageSlug)
            return redirect(Backend::url('wbry/content/items'));
        else
            return back();
    }

    /**
     * @throws
     */
    public function onCreateItem()
    {
        $errors = function ($langSlug)
        {
            Flash::error(Lang::get($langSlug));
            return $this->listRefresh();
        };

        if (! $this->isItemCreate())
            return $errors('wbry.content::content.errors.non_item_create');

        $title = null;
        switch ($formType = post('formType'))
        {
            case self::CONTENT_ITEM_ADD_NEW:
            {
                if (! $this->isItemCreateNewTmp())
                    return $errors('wbry.content::content.errors.non_item_create_new_tmp');

                $validator = Validator::make(post(), [
                    'title' => 'required|between:2,255',
                    'name'  => 'required|between:2,255|alpha_dash',
                ]);
                $validator->setAttributeNames([
                    'title' => Lang::get('wbry.content::content.items.title_label'),
                    'name'  => Lang::get('wbry.content::content.items.name_label'),
                ]);
                if ($validator->fails())
                    throw new ValidationException($validator);

                $title = post('title', '');
                $name  = post('name', '');
                $attr  = ['item_title' => $title];
                $this->addContentItem($this->page, $name, self::CONTENT_ITEM_ADD_NEW, $attr);
                break;
            }

            case self::CONTENT_ITEM_ADD_READY:
            case self::CONTENT_ITEM_ADD_SECTION:
            {
                if (! $this->isItemCreateReadyTmp())
                    return $errors('wbry.content::content.errors.non_item_create_ready_tmp');

                if (empty($this->contentItemList) && empty($this->contentItemSectionsList))
                    throw new ApplicationException(Lang::get('wbry.content::content.popup.block.field_ready_tmp_empty'));

                $name = post('readyTmp', '');
                $attr = [];
                if (! $name || ! is_string($name) || ! preg_match("/^(ready_|section_).+?/i", $name, $m))
                    break;
                if (! $name = preg_replace("/^(ready_|section_)/i", '', $name))
                    break;
                if ($m[1] == 'ready_')
                    $formType = self::CONTENT_ITEM_ADD_READY;
                else
                {
                    $formType = self::CONTENT_ITEM_ADD_SECTION;
                    $attr['section_name'] = $name;
                    $name .= '_'. str_random(8);
                }

                $this->addContentItem($this->page, $name, $formType, $attr);
                $title = $this->getListTitle($name, null);
                break;
            }
        }

        $data = $this->listRefresh();
        $data['#createItemPopup'] = $this->makePartial('create_item_popup');

        if ($title)
            Flash::success(Lang::get('wbry.content::content.success.create_item', ['itemName' => $title]));

        return $data;
    }

    public function onRenameItem()
    {
        $errors = function ($langSlug)
        {
            Flash::error(Lang::get($langSlug));
            return $this->listRefresh();
        };

        $isItemRenameTitle = $this->isItemRenameTitle();
        $isItemRenameSlug  = $this->isItemRenameSlug();
        if (! $this->isItemRename() || (! $isItemRenameTitle && ! $isItemRenameSlug))
            return $errors('wbry.content::content.errors.non_item_rename');

        $oldName = post('old_name','');
        if (! $oldName || ! is_string($oldName))
            return $this->listRefresh();

        $result = $this->renameContentItem($this->page, $oldName, [
            'new_title' => $isItemRenameTitle ? post('title', '') : '',
            'new_slug' => $isItemRenameSlug ? post('name', '') : '',
        ]);
        if ($result)
            Flash::success(Lang::get('wbry.content::content.success.rename_item'));

        return $this->listRefresh();
    }

    public function onDelete_index()
    {
        if (! $this->isItemDelete())
        {
            Flash::error(Lang::get('wbry.content::content.errors.non_item_delete'));
            return $this->listRefresh();
        }

        $return = null;
        Db::transaction(function () use (&$return)
        {
            $delItems = [];
            ItemModel::extend(function($model) use (&$delItems) {
                $model->bindEvent('model.afterDelete', function () use ($model, &$delItems) {
                    $delItems[] = $model->name;
                });
            });
            $return = $this->extendableCall('index_onDelete', []);
            try {
                $this->deleteContentItems($this->page, $delItems);
            } catch (ApplicationException $e) {
                Flash::forget();
                throw $e;
            }
        });
        return $return;
    }

    public function onDelete_update($id = null)
    {
        if (is_numeric($id) && $id > 0)
        {
            if (! $this->isItemDelete())
                Flash::error(Lang::get('wbry.content::content.errors.non_item_delete'));
            else
            {
                $return = null;
                Db::transaction(function () use (&$return, $id)
                {
                    ItemModel::extend(function($model) {
                        $model->bindEvent('model.afterDelete', function () use ($model) {
                            $this->deleteContentItems($this->page, [$model->name]);
                        });
                    });
                    $return = $this->extendableCall('update_onDelete', [$id]);
                });
                return $return;
            }
        }

        return null;
    }

    public function onReorder()
    {
        return $this->extendableCall('onReorder', []);
    }

    /*
     * Filters
     */

    public function listExtendQuery($query)
    {
        $query->page($this->page);
    }

    public function reorderExtendQuery($query)
    {
        $query->page($this->page);
    }

    public function formExtendFields($form, $fields)
    {
        if ($form->arrayName === 'Item')
        {
            $activeForm = isset($form->data->name) ? $this->getActiveContentItemForm($this->page, $form->data->name) : null;
            if (! empty($activeForm))
            {
                $itemForm = [
                    'type' => 'nestedform',
                    'usePanelStyles' => false,
                    'form' => $activeForm,
                ];
                if ($this->transLocales)
                {
                    $form->addTabFields(['items' => array_merge($itemForm, ['tab' => strtoupper($this->defaultLocale)])]);
                    foreach ($this->transLocales as $langCode)
                        $form->addTabFields([$langCode => array_merge($itemForm, ['tab' => strtoupper($langCode)])]);
                }
                else
                    $form->addFields(['items' => $itemForm]);
            }
            else
            {
                $form->addFields(['no_item' => [
                    'type' => 'partial',
                    'span' => 'full',
                    'path' => is_array($activeForm) ? 'content_item_form_empty' : 'content_item_form_missing',
                ]]);
            }
        }
        elseif ($form->arrayName === 'Page')
        {
            #
        }
    }

    public function listExtendColumns($list)
    {
        if ($this->isItemRename())
        {
            $list->addColumns(['edit_btn' => [
                'label'      => '',
                'type'       => 'partial',
                'searchable' => false,
                'sortable'   => false,
                'clickable'  => false,
                'width'      => '60px',
                'cssClass'   => 'column-button contentItemRenameBtn',
                'path'       => '$/wbry/content/controllers/items/_column_edit.htm',
            ]]);
        }
    }

    /*
     * Action control
     */

    public function index(...$d)   { return $this->actionView(...$d); }
    public function create(...$d)  { return $this->actionView(...$d); }
    public function update(...$d)  { return $this->actionView(...$d); }
    public function preview(...$d) { return $this->actionView(...$d); }

    public function __call($name, $arguments)
    {
        switch ($name)
        {
            case $this->actionAjax: return $this->actionAjax(...$arguments); break;
            case $this->action:     return $this->actionView(...$arguments); break;
        }
        return parent::__call($name, $arguments);
    }

    protected function actionAjax($id = null)
    {
        if (method_exists($this, $this->ajaxHandler))
            return call_user_func_array([$this, $this->ajaxHandler], func_get_args());

        $action = (is_numeric($id) && $id > 0) ? 'update' : 'index';

        $thisMethodName = $this->ajaxHandler .'_'. $action;
        if (method_exists($this, $thisMethodName))
            return call_user_func_array([$this, $thisMethodName], func_get_args());

        $methodName = $action .'_'. $this->ajaxHandler;
        if ($this->methodExists($methodName))
        {
            $this->actionAjax = null;
            return call_user_func_array([$this, $methodName], func_get_args());
        }
        return null;
    }

    protected function actionView($id = 0)
    {
        $this->actionId = $id;

        if ($this->actionId === 'reorder')
            return $this->actionReorderView();
        elseif (! is_numeric($this->actionId))
            return $this->makeView404();
        elseif ((! $this->actionId || $this->isContentItemError) && $this->fatalError)
            return $this->makeViewContentFile('fatal-error');
        elseif (! $this->menuList)
            return $this->makeViewContentFile('no-content');
        else
            return $this->actionId ? $this->actionFormView() : $this->actionListView();
    }

    protected function actionListView()
    {
        $this->pageTitle = $this->getListPageTitle();
        $this->bodyClass = 'compact-container';
        $this->makeLists();
        $this->initForm(PageModel::make());

        return $this->makeViewContentFile('list');
    }

    protected function actionReorderView()
    {
        if (! $this->isItemSort())
            return $this->makeView404();

        $this->listTitle = $this->getListPageTitle();
        $this->pageTitle = Lang::get('wbry.content::content.list.sort_btn');
        $this->reorder();

        return $this->makeViewContentFile('reorder');
    }

    protected function actionFormView()
    {
        if ($this->actionId < 1 || ! ($model = ItemModel::find($this->actionId)))
            return $this->makeView404();

        if ($this->transLocales)
        {
            foreach ($this->transLocales as $locale)
            {
                $itemsData = $model->getAttributeTranslated('items', $locale) ?: $model->items;
                $model->setAttribute($locale, $itemsData);
            }
        }

        $title = $this->getListTitle($model->name, $model->name);
        $this->pageTitle = Lang::get('wbry.content::content.form.title', ['title' => $title]);
        $this->listTitle = $this->getListPageTitle();
        $this->initForm($model);

        return $this->makeViewContentFile('update');
    }

    /*
     * Views
     */

    protected function makeView404()
    {
        return Response::make(View::make('backend::404'), 404);
    }

    protected function makeViewContentFile($fileHtm)
    {
        Block::append('head', $this->makePartial('head'));
        Block::append('body', $this->makePartial('additional_content'));

        return Response::make($this->makeView($fileHtm), $this->statusCode);
    }
}
