<?php

namespace Wbry\Content\Controllers;

use Yaml;

use App;
use Lang;
use View;
use Event;
use Flash;
use Session;
use Response;
use Validator;
use Exception;
use BackendMenu;
use Backend\Classes\Controller;
use Wbry\Content\Models\Item as ItemModel;
use October\Rain\Exception\ValidationException;
use October\Rain\Exception\ApplicationException;

/**
 * Items controller
 *
 * @package Wbry\Content\Controllers
 * @author Diamond Systems
 */
class Items extends Controller
{
    use \Wbry\Content\Classes\Traits\ContentItemsParse;

    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['wbry.content.items'];

    public $menuName     = null;
    public $listTitle    = null;
    public $actionAjax   = null;
    public $actionId     = null;
    public $ajaxHandler  = null;

    public $isContentItemError = false;

    /*
     * Initialize
     */

    public function __construct()
    {
        parent::__construct();

        if (! $this->action)
            return;

        # set lang
        # ==========
        if (Session::has('locale'))
        {
            $locale = Session::get('locale');
            if ($locale !== App::getLocale())
                App::setLocale($locale);
        }

        # load
        # ======
        $this->parseContentItems();
        $this->addActionMenu();
        $this->addDynamicActionMethods();
        $this->addAssets();
    }

    protected function parseContentItems()
    {
        try {
            $this->parseContentItemsConfig($this->action);
        }
        catch (Exception $e) {
            $this->isContentItemError = true;
            $this->handleError($e);
        }
    }

    protected function addActionMenu()
    {
        BackendMenu::setContext('Wbry.Content', 'items');
        BackendMenu::setContextSideMenu($this->action);

        Event::listen('backend.menu.extendItems', function($menu) {
            $menu->addSideMenuItems('Wbry.Content', 'items', $this->menuList);
        });

        $this->menuName = isset($this->menuList[$this->action]) ? $this->menuList[$this->action]['label'] : '';
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

    protected function addAssets()
    {
        # Custom
        $this->addCss('/plugins/wbry/content/assets/css/backend/main.css');
        $this->addJs('/plugins/wbry/content/assets/js/backend/items_page.js');

        # framework extras
        $this->addJs('/modules/system/assets/js/framework.extras.js');
        $this->addCss('/modules/system/assets/css/framework.extras.css');
    }

    public function getListTitle($itemSlug, $default = '-')
    {
        return $this->contentItemList[$this->action][$itemSlug] ?? $default;
    }

    public function getListPageTitle()
    {
        return $this->menuName ?: Lang::get('wbry.content::content.list.title');
    }

    public function getReadyItemsList()
    {
        if (! $this->contentItemList || ! $this->action)
            return [];

        return array_diff_key(
            $this->contentItemList[$this->action],
            ItemModel::page($this->action)->lists('id', 'name')
        );
    }

    /*
     * Ajax
     */

    /**
     * @throws
     */
    public function onCreateItem()
    {
        if (! $this->action)
        {
            Flash::error(Lang::get('wbry.content::content.errors.empty_action'));
            return $this->listRefresh();
        }

        Validator::extend('no_exists_item', function($attr, $value) {
            return (! ItemModel::item($this->action, $value)->count());
        });

        if (post('formType') == 'ready')
        {
            $name = post('readyTmp');

            if (empty($this->contentItemList))
                throw new ApplicationException(Lang::get('wbry.content::content.list.form_ready_tmp_empty'));

            Validator::extend('ready_item', function($attr, $value) {
                return ($value && isset($this->contentItemList[$this->action][$value]));
            });
            $validator = Validator::make(post(), [
                'readyTmp' => 'required|no_exists_item|ready_item',
            ], [
                'no_exists_item' => Lang::get('wbry.content::content.errors.no_exists_item', ['itemSlug' => $name]),
                'ready_item' => Lang::get('wbry.content::content.errors.no_item_tmp', ['itemSlug' => $name]),
            ]);
            $validator->setAttributeNames([
                'readyTmp' => Lang::get('wbry.content::content.list.form_ready_tmp_label'),
            ]);

            if ($validator->fails())
                throw new ValidationException($validator);

            $title = $this->contentItemList[$this->action][$name];
        }
        else
        {
            $title = post('title');
            $name  = post('name');

            $validator = Validator::make(post(), [
                'title' => 'required|between:3,255',
                'name'  => 'required|between:3,255|alpha_dash|no_exists_item',
            ], [
                'no_exists_item' => Lang::get('wbry.content::content.errors.no_exists_item', ['itemSlug' => $name]),
            ]);
            $validator->setAttributeNames([
                'title' => Lang::get('wbry.content::content.items.title_label'),
                'name'  => Lang::get('wbry.content::content.items.name_label'),
            ]);

            if ($validator->fails())
                throw new ValidationException($validator);

            $this->addContentItem($this->action, $name, $title);
        }

        ItemModel::create([
            'page' => $this->action,
            'name' => $name,
        ]);

        $data = $this->listRefresh();
        $data['#createItemPopup'] = $this->makePartial('create_item_popup');

        Flash::success(Lang::get('wbry.content::content.success.create_item', ['itemName' => $title]));

        return $data;
    }

    /*
     * Filters
     */

    public function listExtendQuery($query)
    {
        $query->where('page', $this->action);
    }

    public function formExtendFieldsBefore($form)
    {
        # items
        # =======
        if (isset($form->fields['items']) && empty($form->fields['items']['form']))
        {
            $itemName = $form->data->name;
            if (! empty($this->activeContentItemForm[$itemName]['form']))
                $form->fields['items']['form'] = $this->activeContentItemForm[$itemName]['form'];
            else
            {
                $form->fields['items']['form'] = ['fields' => [
                    'no_item' => [
                        'type' => 'partial',
                        'span' => 'full',
                        'path' => isset($this->activeContentItemForm[$itemName]) ? 'content_item_form_empty' : 'content_item_form_missing',
                    ]
                ]];
            }
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
        if ($this->actionAjax === $name)
            return $this->actionAjax(...$arguments);
        elseif ($name === $this->action)
            return $this->actionView(...$arguments);

        return parent::__call($name, $arguments);
    }

    protected function actionAjax($action = 'index', $id = null)
    {
        if (method_exists($this, $this->ajaxHandler))
            return call_user_func_array([$this, $this->ajaxHandler], func_get_args());

        $methodName = $action .'_'. $this->ajaxHandler;
        if ($this->methodExists($methodName))
        {
            $this->actionAjax = null;
            return call_user_func_array([$this, $methodName], [$id]);
        }
        return false;
    }

    protected function actionView($id = 0)
    {
        $this->actionId = $id;

        if (! is_numeric($this->actionId))
            return $this->makeView404();
        if ((! $this->actionId || $this->isContentItemError) && $this->fatalError)
            return $this->makeViewContentFile('fatal-error');
        elseif (! $this->menuList)
            return $this->makeViewContentFile('no-content');
        else
            return $this->actionId ? $this->actionFormView() : $this->actionListView();
    }

    protected function actionListView()
    {
        $this->pageTitle = $this->getListPageTitle();
        $this->bodyClass = 'slim-container';
        $this->makeLists();

        return $this->makeViewContentFile('list');
    }

    protected function actionFormView()
    {
        if ($this->actionId < 1 || ! ($model = ItemModel::find($this->actionId)))
            return $this->makeView404();

        $title = $this->contentItemList[$this->action][$model->name] ?? $model->name;
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
        return Response::make($this->makeView($fileHtm), $this->statusCode);
    }
}
