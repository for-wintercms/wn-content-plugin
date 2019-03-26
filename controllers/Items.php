<?php

namespace Wbry\Content\Controllers;

use Lang;
use View;
use Response;
use BackendMenu;
use Backend\Classes\Controller;
use Wbry\Content\Models\Item as ItemModel;

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

    public $currentMenu = null;
    public $listTitle = '';

    public function __construct()
    {
        parent::__construct();

        if (! $this->action)
            $this->makeView404();

        BackendMenu::setContext('Wbry.Content', 'items');
        $listSideMenu = BackendMenu::listSideMenuItems();
        $this->currentMenu = $listSideMenu['item-'.$this->action] ?? [];

        if (! $this->currentMenu)
            $this->makeView404();

        $this->addDynamicMethod($this->action, self::class);
    }

    /*
     * Action control
     */

    public function __call($name, $arguments)
    {
        if ($name === $this->action)
            return $this->actionView(...$arguments);

        return parent::__call($name, $arguments);
    }

    protected function makeView404()
    {
        return Response::make(View::make('backend::404'), 404);
    }

    protected function actionView($action = 'list', $id = 0)
    {
        return ($action === 'list') ? $this->actionListView() : $this->actionFormView($action, $id);
    }

    protected function actionListView()
    {
        $this->pageTitle = $this->currentMenu->label;
        $this->bodyClass = 'slim-container';
        $this->makeLists();

        return Response::make($this->makeView('list'), $this->statusCode);
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

        return Response::make($this->makeView($action), $this->statusCode);
    }
}
