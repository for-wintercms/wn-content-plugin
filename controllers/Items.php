<?php

namespace ForWinterCms\Content\Controllers;

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
use ForWinterCms\Content\Models\Item as ItemModel;
use ForWinterCms\Content\Models\Page as PageModel;
use ForWinterCms\Content\Classes\IconList;
use ForWinterCms\Content\Classes\Interfaces\ContentItems;
use Winter\Storm\Exception\ValidationException;
use Winter\Storm\Exception\ApplicationException;

/**
 * Items controller
 *
 * @package ForWinterCms\Content\Controllers
 */
class Items extends Controller implements ContentItems
{
    use \ForWinterCms\Content\Classes\Traits\ContentItemsParse;

    public $implement = [
        'Backend\Behaviors\ListController',
        'ForWinterCms\Content\Classes\Behaviors\FormController',
        'Backend\Behaviors\ReorderController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = ['forwintercms.content.items'];

    public $page          = null;
    public $locales       = null;
    public $defaultLocale = null;
    public $transLocales  = null;
    public $listTitle     = null;
    public $actionAjax    = null;
    public $actionId      = null;
    public $ajaxHandler   = null;
    public $currentPage   = null;

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
        $this->page = request()->segment(5) ?: 'index';
        $this->currentPage = $this->getPageModel($this->page);

        if (! $this->currentPage)
        {
            $this->currentPage = PageModel::orderBy('order', 'asc')->first();
            if ($this->currentPage)
                $this->page = $this->currentPage->slug;
        }

        BackendMenu::setContext('ForWinterCms.Content', 'items');
        BackendMenu::setContextSideMenu($this->page);

        Event::listen('backend.menu.extendItems', function($menu)
        {
            # submenu attributes
            # ======================
            $submenu = [];
            $isClonePage = $this->isPageClone();

            foreach (PageModel::select('title','slug','icon','order')->get() as $page)
            {
                $submenu[$page->slug] = [
                    'label' => $page->title,
                    'slug'  => $page->slug,
                    'icon'  => $page->icon,
                    'order' => $page->order,
                    'url'   => $this->getPageUrl($page->slug),
                    'attributes' => [
                        'data-submenu-title' => $page->title,
                        'data-submenu-slug'  => $page->slug,
                        'data-submenu-icon'  => $page->icon,
                        'data-submenu-order' => $page->order,
                        'data-submenu-clone' => $isClonePage,
                    ],
                ];
            }

            # add new page btn
            # ======================
            if ($this->isPageCreate())
            {
                $submenu['create_new_page'] = [
                    'label' => Lang::get('forwintercms.content::content.submenu.create_page_btn'),
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
            $menu->addSideMenuItems('ForWinterCms.Content', 'items', $submenu);
        });
    }

    protected function addDynamicActionMethods()
    {
        if (! $this->currentPage)
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
                unset($model->attributes['page_slug'], $model->attributes['title']);
            });
            $funAfterData = function() use ($model) {
                $model->attributes['page_slug'] = $this->page;
                $model->attributes['title'] = $this->getListTitle($model->name, $model->name);
            };
            $model->bindEvent('model.afterSave', $funAfterData);
            $model->bindEvent('model.afterFetch', $funAfterData);
        });
    }

    protected function addAssets()
    {
        # Custom
        $this->addCss('/plugins/forwintercms/content/assets/css/backend/main.css', '1696488639');
        $this->addJs('/plugins/forwintercms/content/assets/js/backend/items_page.js', '1696488639');

        # framework extras
        $this->addJs('/modules/system/assets/js/framework.extras.js');
        $this->addCss('/modules/system/assets/css/framework.extras.css');
    }

    /*
     * Functional limitations
     */

    public function isItemCreate()
    {
        return $this->getEventResult('forwintercms.content.isItemCreate');
    }

    public function isItemCreateNewTmp()
    {
        return $this->getEventResult('forwintercms.content.isItemCreateNewTmp');
    }

    public function isItemCreateReadyTmp()
    {
        return $this->getEventResult('forwintercms.content.isItemCreateReadyTmp');
    }

    public function isItemRename()
    {
        return $this->getEventResult('forwintercms.content.isItemRename');
    }

    public function isItemRenameTitle()
    {
        return $this->getEventResult('forwintercms.content.isItemRenameTitle');
    }

    public function isItemRenameSlug()
    {
        return $this->getEventResult('forwintercms.content.isItemRenameSlug');
    }

    public function isItemDelete()
    {
        return $this->getEventResult('forwintercms.content.isItemDelete');
    }

    public function isItemSort()
    {
        return $this->getEventResult('forwintercms.content.isItemSort');
    }

    public function isPageCreate()
    {
        return $this->getEventResult('forwintercms.content.isPageCreate');
    }

    public function isPageClone()
    {
        return $this->getEventResult('forwintercms.content.isPageClone');
    }

    public function isPageEdit()
    {
        return $this->getEventResult('forwintercms.content.isPageEdit');
    }

    public function isPageDelete()
    {
        return $this->getEventResult('forwintercms.content.isPageDelete');
    }

    public function hasAccessItemsChanges()
    {
        static $itemsChanges;
        return $itemsChanges ?: ($itemsChanges = $this->user->hasAccess('forwintercms.content.items_changes'));
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

    public function getPageUrl(string $pageSlug = NULL)
    {
        return Backend::url('forwintercms/content/items/'. ($pageSlug ?: $this->page));
    }

    public function getListPageTitle()
    {
        static $menuName;
        if ($menuName)
            return $menuName;

        $menuName = $this->currentPage->title ?? Lang::get('forwintercms.content::content.list.title');
        return $menuName;
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

    public function getPageModel($pageSlug)
    {
        if (empty($pageSlug) || ! is_string($pageSlug))
            return null;
        return PageModel::slug($pageSlug)->first();
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
            Flash::error(Lang::get('forwintercms.content::content.errors.non_page_create'));

        $this->buildContentItemPage(post());

        Flash::success(Lang::get('forwintercms.content::content.success.create_page', ['page' => post('title')]));

        $pageSlug = post('slug');
        if ($this->getPageModel($pageSlug))
            return redirect($this->getPageUrl($pageSlug));
        else
            return back();
    }

    /**
     * @throws
     */
    public function onClonePage()
    {
        if (! $this->isPageClone())
            Flash::error(Lang::get('forwintercms.content::content.errors.non_page_clone'));

        $this->buildContentItemPage(post(), self::CONTENT_ITEM_ACTION_CLONE, post('old_slug'));

        Flash::success(Lang::get('forwintercms.content::content.success.clone_page', ['page' => post('title')]));

        $pageSlug = post('slug');
        if ($this->getPageModel($pageSlug))
            return redirect($this->getPageUrl($pageSlug));
        else
            return back();
    }

    /**
     * @throws
     */
    public function onEditPage()
    {
        if (! $this->isPageEdit())
            Flash::error(Lang::get('forwintercms.content::content.errors.non_page_edit'));

        $pageData    = post('Page');
        $pageSlug    = $pageData['slug'] ?? '';
        $oldPageSlug = $pageData['old_slug'] ?? '';

        $this->buildContentItemPage($pageData, self::CONTENT_ITEM_ACTION_EDIT, $oldPageSlug);

        $page = $this->getPageModel($pageSlug);
        $this->pageSave($page);

        Flash::success(Lang::get('forwintercms.content::content.success.edit_page', ['page' => post('title')]));

        if ($page)
            return redirect($this->getPageUrl($pageSlug));
        else
            return back();
    }

    /**
     * @throws
     */
    public function onDeletePage()
    {
        if (! $this->isPageDelete())
            Flash::error(Lang::get('forwintercms.content::content.errors.non_page_delete'));

        $pageSlug = post('slug');
        $this->deleteContentItemPage($pageSlug);

        Flash::success(Lang::get('forwintercms.content::content.success.delete_page'));

        if ($this->page == $pageSlug)
            return redirect(Backend::url('forwintercms/content/items'));
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
            return $errors('forwintercms.content::content.errors.non_item_create');

        $title = null;
        switch ($formType = post('formType'))
        {
            case self::CONTENT_ITEM_ADD_NEW:
            {
                if (! $this->isItemCreateNewTmp())
                    return $errors('forwintercms.content::content.errors.non_item_create_new_tmp');

                $validator = Validator::make(post(), [
                    'title' => 'required|between:2,255',
                    'name'  => 'required|between:2,255|alpha_dash',
                ]);
                $validator->setAttributeNames([
                    'title' => Lang::get('forwintercms.content::content.items.title_label'),
                    'name'  => Lang::get('forwintercms.content::content.items.name_label'),
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
                    return $errors('forwintercms.content::content.errors.non_item_create_ready_tmp');

                if (empty($this->contentItemList) && empty($this->contentItemSectionsList))
                    throw new ApplicationException(Lang::get('forwintercms.content::content.popup.block.field_ready_tmp_empty'));

                $name = post('readyTmp');
                $attr = [];
                if (! is_string($name) || ! preg_match("/^(ready_|section_).+?/i", $name, $m))
                    break;
                if (! $name = preg_replace("/^(ready_|section_)/i", '', $name))
                    break;
                if ($m[1] == 'ready_')
                    $formType = self::CONTENT_ITEM_ADD_READY;
                else
                {
                    $formType = self::CONTENT_ITEM_ADD_SECTION;
                    $attr['section_name'] = $name;
                    $name .= '_'. str_random(7);
                }
                $attr['item_title'] = post('block_title');
                $attr['item_key']   = post('block_key');

                $this->addContentItem($this->page, $name, $formType, $attr);
                $title = $this->getListTitle($name, null);
                break;
            }
        }

        $data = $this->listRefresh();
        $data['#createItemPopup'] = $this->makePartial('create_item_popup');

        if ($title)
            Flash::success(Lang::get('forwintercms.content::content.success.create_item', ['itemName' => $title]));

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
            return $errors('forwintercms.content::content.errors.non_item_rename');

        $oldName = post('old_name','');
        if (! $oldName || ! is_string($oldName))
            return $this->listRefresh();

        $result = $this->renameContentItem($this->page, $oldName, [
            'new_title' => $isItemRenameTitle ? post('title', '') : '',
            'new_slug' => $isItemRenameSlug ? post('name', '') : '',
        ]);
        if ($result)
            Flash::success(Lang::get('forwintercms.content::content.success.rename_item'));

        return $this->listRefresh();
    }

    public function onDelete_index()
    {
        if (! $this->isItemDelete())
        {
            Flash::error(Lang::get('forwintercms.content::content.errors.non_item_delete'));
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
                Flash::error(Lang::get('forwintercms.content::content.errors.non_item_delete'));
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

    public function onGetTranslateItems($id = null)
    {
        $ret = [];
        if ($this->transLocales && is_numeric($id) && $id > 0 && ($model = ItemModel::find($id))) {
            $ret[$this->defaultLocale] = $model->getAttributeTranslated('items', $this->defaultLocale);
            foreach ($this->transLocales as $locale)
                $ret[$locale] = $model->getAttributeTranslated('items', $locale);
        }

        return ['result' => $ret];
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
                /*
                if ($this->transLocales)
                {
                    $form->addTabFields(['items' => [
                        'type' => 'nestedform',
                        'usePanelStyles' => false,
                        'tab' => 'Main',
                        'form' => $activeForm,
                    ]]);
                    $form->addTabFields(['translates' => [
                        'type' => 'nestedform',
                        'usePanelStyles' => false,
                        'tab' => 'Translate',
                        'form' => ['secondaryTabs' => [
                            'fields' => [
                                'items' => [
                                    'type' => 'nestedform',
                                    'usePanelStyles' => false,
                                    'tab' => 'EN',
                                    'form' => $activeForm,
                                ],
                                'ru' => [
                                    'type' => 'nestedform',
                                    'usePanelStyles' => false,
                                    'tab' => 'RU',
                                    'form' => $activeForm,
                                ],
                                'uz' => [
                                    'type' => 'nestedform',
                                    'usePanelStyles' => false,
                                    'tab' => 'UZ',
                                    'form' => $activeForm,
                                ],
                            ],
                        ]],
                    ]]);
//                    $form->addSecondaryTabFields(['items' => array_merge($itemForm, ['tab' => strtoupper($this->defaultLocale)])]);
//                    foreach ($this->locales as $langCode)
//                        $form->addSecondaryTabFields([$langCode => array_merge($itemForm, ['tab' => strtoupper($langCode), 'cssClass' => 'langLocale'])]);
                }
                else
                */
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
            if ($this->isPageEdit())
            {
                $form->model->setAttribute('old_slug', ($form->model->slug ?? ''));
                $mainTab = Lang::get('forwintercms.content::content.pages.tab_main');
                $form->addFields([
                    'section_settings_page' => [
                        'label' => Lang::get('forwintercms.content::content.pages.section_settings'),
                        'span'  => 'full',
                        'type'  => 'section',
                        'tab'  => $mainTab,
                    ],
                    'title' => [
                        'label' => Lang::get('forwintercms.content::content.pages.field_title'),
                        'span'  => 'left',
                        'type'  => 'text',
                        'tab'  => $mainTab,
                    ],
                    'slug' => [
                        'label' => Lang::get('forwintercms.content::content.pages.field_slug'),
                        'span'  => 'right',
                        'type'  => 'text',
                        'preset' => 'title',
                        'tab'  => $mainTab,
                    ],
                    'icon' => [
                        'label' => Lang::get('forwintercms.content::content.pages.field_icon'),
                        'span'  => 'left',
                        'type'  => 'dropdown',
                        'options' => 'getIconListDropDown',
                        'tab'  => $mainTab,
                    ],
                    'order' => [
                        'label' => Lang::get('forwintercms.content::content.pages.field_order'),
                        'span'  => 'right',
                        'type'  => 'number',
                        'tab'  => $mainTab,
                    ],
                    'old_slug' => [
                        'type' => 'text',
                        'attributes' => ['style' => 'display:none;'],
                        'tab'  => $mainTab,
                    ],
                ], Event::fire('forwintercms.content.pageSettingsMainTab', [], true));
            }
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
                'path'       => '$/forwintercms/content/controllers/items/_column_edit.htm',
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
        elseif (! $this->currentPage)
            return $this->makeViewContentFile('no-content');
        else
            return $this->actionId ? $this->actionFormView() : $this->actionListView();
    }

    protected function actionListView()
    {
        $this->pageTitle = $this->getListPageTitle();
        $this->bodyClass = 'compact-container';
        $this->makeLists();
        $this->initForm($this->currentPage);

        return $this->makeViewContentFile('list');
    }

    protected function actionReorderView()
    {
        if (! $this->isItemSort())
            return $this->makeView404();

        $this->listTitle = $this->getListPageTitle();
        $this->pageTitle = Lang::get('forwintercms.content::content.list.sort_btn');
        $this->reorder();

        return $this->makeViewContentFile('reorder');
    }

    protected function actionFormView()
    {
        if ($this->actionId < 1 || ! ($model = ItemModel::find($this->actionId)))
            return $this->makeView404();

        $this->addJs('/plugins/forwintercms/content/assets/js/backend/translate_items.js', '1696488639');

        /*
        if ($this->transLocales)
        {
            foreach ($this->transLocales as $locale)
            {
                $itemsData = $model->getAttributeTranslated('items', $locale) ?: $model->items;
                $model->setAttribute($locale, $itemsData);
            }
        }
        */

        $title = $this->getListTitle($model->name, $model->name);
        $this->pageTitle = Lang::get('forwintercms.content::content.form.title', ['title' => $title]);
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
