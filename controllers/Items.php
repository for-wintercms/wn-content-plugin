<?php

namespace Wbry\Content\Controllers;

use App;
use Lang;
use View;
use Event;
use Session;
use Response;
use BackendMenu;
use Backend\Classes\Controller;
use Wbry\Content\Models\Item as ItemModel;
use October\Rain\Exception\ApplicationException;

/**
 * Items controller
 *
 * @package Wbry\Content\Controllers
 * @author Diamond Systems
 */
class Items extends Controller
{
    use \Wbry\Content\Classes\Traits\RepeaterParse;

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
    public $actionType   = null;
    public $actionId     = null;
    public $ajaxHandler  = null;

    /*
     * Initialize
     */

    public function __construct()
    {
        parent::__construct();

        if (! $this->action)
            return;

        # set lang
        if (Session::has('locale'))
        {
            $locale = Session::get('locale');
            if ($locale !== App::getLocale())
                App::setLocale($locale);
        }

        # load
        $this->parseRepeatersConfig(true);
        $this->addActionMenu();
        $this->addDynamicActionMethods();
        $this->addAssets();
    }

    protected function addActionMenu()
    {
        BackendMenu::setContext('Wbry.Content', 'items');
        BackendMenu::setContextSideMenu($this->action);

        $thisObj = &$this;
        Event::listen('backend.menu.extendItems', function($menu) use($thisObj) {
            $menu->addSideMenuItems('Wbry.Content', 'items', $thisObj->menuList);
        });

        $this->menuName = $thisObj->menuList[$this->action] ? $thisObj->menuList[$this->action]['label'] : '';
    }

    protected function addDynamicActionMethods()
    {
        if (! $this->menuList)
            return;

        $this->addDynamicMethod($this->action, self::class);
        if ($this->ajaxHandler = $this->getAjaxHandler())
        {
            $this->actionAjax = $this->action.'_'.$this->ajaxHandler;
            $this->addDynamicMethod($this->actionAjax, self::class);
        }
    }

    protected function addAssets()
    {
        $this->addCss('/plugins/wbry/content/assets/css/backend/main.css');
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
        $repeater = null;
        if (preg_match("#::(onAddItem|onRemoveItem)$#", $this->ajaxHandler))
        {
            $data = post('Item');
            $repeater = $data['repeater'] ?? null;

            if (! $repeater)
                throw new ApplicationException(Lang::get('wbry.content::lang.controllers.items.errors.items_empty'));

            // TODO validate change repeater option
        }
        elseif ($this->actionType === 'update' && $form->data->repeater)
            $repeater = $form->data->repeater;

        if ($repeater && isset($form->fields['items']) && empty($form->fields['items']['form']))
        {
            if (! isset($this->repeaters[$repeater]))
                throw new ApplicationException(Lang::get('wbry.content::lang.controllers.items.errors.items_no_repeater', ['repeater' => $repeater]));

            $form->fields['items']['form'] = $this->repeaters[$repeater];
        }

        # repeater (list)
        # ================
        if (isset($form->fields['repeater']) && empty($form->fields['repeater']['options']))
            $form->fields['repeater']['options'] = $this->repeaterList;

        # name (default)
        # ===============
        if (isset($form->fields['name']) && empty($form->fields['name']['default']))
            $form->fields['name']['default'] = $this->action .'-item'.rand(100, 999);
    }

    public function formExtendFields($form)
    {
        $form->addFields([
            'page' => [
                'type' => 'text',
                'cssClass' => 'd-none',
                'default' => $this->action
            ]
        ]);
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
        $methodName = $action .'_'. $this->ajaxHandler;
        if ($this->methodExists($methodName))
        {
            $result = call_user_func_array([$this, $methodName], [$id]);
            return $result ?: true;
        }
        return false;
    }

    protected function actionView($action = 'list', $id = 0)
    {
        $this->actionType = $action;
        $this->actionId   = $id;

        if (! is_numeric($this->actionId))
            return $this->makeView404();
        if (($action === 'list' || $this->isRepeaterError) && $this->fatalError)
            return $this->makeViewContentFile('fatal-error');
        elseif (! $this->menuList)
            return $this->makeViewContentFile('no-content');
        else
            return ($action === 'list') ? $this->actionListView() : $this->actionFormView();
    }

    protected function actionListView()
    {
        $this->pageTitle = $this->menuName;
        $this->bodyClass = 'slim-container';
        $this->makeLists();

        return $this->makeViewContentFile('list');
    }

    protected function actionFormView()
    {
        switch ($this->actionType)
        {
            case 'create':
                $model = $this->formCreateModelObject();
                $this->pageTitle = Lang::get('wbry.content::lang.controllers.items.create_title');
                break;

            case 'update':
                if (! is_numeric($this->actionId) || $this->actionId < 1 || ! ($model = ItemModel::find($this->actionId)))
                    return $this->makeView404();

                $this->pageTitle = Lang::get('wbry.content::lang.controllers.items.update_title', ['title' => $model->name]);
                break;

            default: return $this->makeView404();
        }

        $this->listTitle = $this->menuName ?: Lang::get('wbry.content::lang.controllers.items.list_title');
        $this->initForm($model);

        return $this->makeViewContentFile($this->actionType);
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
