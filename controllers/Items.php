<?php

namespace Wbry\Content\Controllers;

use App;
use File;
use Yaml;
use Lang;
use View;
use Session;
use Request;
use Response;
use BackendMenu;
use Exception;
use Cms\Classes\Theme as CmsTheme;
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
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['wbry.content.items'];

    public $menuList    = null;
    public $currentMenu = null;
    public $listTitle   = null;
    public $actionAjax  = null;
    public $ajaxHandler = null;

    public $isRepeaterError = false;

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
        $this->parseRepeatersConfig();
        $this->addActionMenu();
        $this->addDynamicActionMethods();
        $this->addAssets();
    }

    protected function parseRepeatersConfig()
    {
        $theme = CmsTheme::getActiveTheme();
        $directory = $theme->getPath().'/repeaters';

        if (! File::isDirectory($directory))
            return;

        try {
            foreach (File::files($directory) as $file)
            {
                $fileName = $file->getFilename();
                if (! preg_match("#^config\-(.+?)\.yaml$#i", $fileName))
                    continue;

                $config = Yaml::parseFile($file->getRealPath());

                # menu
                if (empty($config['menu']) || empty($config['menu']['label']) || empty($config['menu']['slug']))
                    throw new ApplicationException(Lang::get('wbry.content::lang.controllers.items.errors.repeater_menu', ['fileName' => $fileName]));

                $this->menuList[$config['menu']['slug']] = [
                    'label' => $config['menu']['label'],
                    'icon'  => $config['menu']['icon'] ?? '',
                ];

                # repeat
                $errRepeater = Lang::get('wbry.content::lang.controllers.items.errors.repeater_list', ['fileName' => $fileName]);
                if (empty($config['repeater']) || ! is_array($config['repeater']))
                    throw new ApplicationException($errRepeater);

                foreach ($config['repeater'] as $rAction => $repeater)
                {
                    if (! isset($repeater['fields']))
                        throw new ApplicationException($errRepeater);
                }
            }
        }
        catch (Exception $e) {
            $this->isRepeaterError = true;
            $this->handleError($e);
        }
    }

    protected function addActionMenu()
    {
        BackendMenu::setContext('Wbry.Content', 'items');
        $listSideMenu = BackendMenu::listSideMenuItems();
        $this->currentMenu = $listSideMenu['item-'.$this->action] ?? [];
    }

    protected function addDynamicActionMethods()
    {
        if (! $this->currentMenu)
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
        if (($action === 'list' || $this->isRepeaterError) && $this->fatalError)
            return $this->makeViewContentFile('fatal-error');
        elseif (! $this->currentMenu)
            return $this->makeViewContentFile('no-content');
        else
            return ($action === 'list') ? $this->actionListView() : $this->actionFormView($action, $id);
    }

    protected function actionListView()
    {
        $this->pageTitle = $this->currentMenu->label;
        $this->bodyClass = 'slim-container';
        $this->makeLists();

        return $this->makeViewContentFile('list');
    }

    protected function actionFormView($action, $id=0)
    {
        switch ($action)
        {
            case 'create':
                $model = $this->formCreateModelObject();
                $this->pageTitle = Lang::get('wbry.content::lang.controllers.items.create_title');
                break;

            case 'update':
                if (! is_numeric($id) || $id < 1 || ! ($model = ItemModel::find($id)))
                    return $this->makeView404();

                $this->pageTitle = Lang::get('wbry.content::lang.controllers.items.update_title', ['title' => $model->title]);
                break;

            default: return $this->makeView404();
        }

        $this->listTitle = $this->currentMenu->label ?? Lang::get('wbry.content::lang.controllers.items.list_title');
        $this->initForm($model);

        return $this->makeViewContentFile($action);
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
