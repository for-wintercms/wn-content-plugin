<?php

namespace Wbry\Content\Classes\Behaviors;

use Db;
use Event;
use Backend\Behaviors\FormController as FormControllerMain;

/**
 * FormController behavior
 * @package Wbry\Content\Classes\Behaviors
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
class FormController extends FormControllerMain
{
    public function pageSave($model)
    {
        $this->context = self::CONTEXT_UPDATE;
        $this->initForm($model);

        $delAttr = [
            'old_slug',
        ];

        Event::fire('wbry.content.pageSaveBefore', [$model, &$delAttr]);

        $model->bindEvent('model.saveInternal', function ($attributes, $options) use ($model, &$delAttr) {
            foreach ($delAttr as $attr)
                unset($model->{$attr});
        });

        $modelsToSave = $this->prepareModelsToSave($model, $this->formWidget->getSaveData());
        Db::transaction(function () use ($modelsToSave) {
            foreach ($modelsToSave as $modelToSave)
                $modelToSave->save(null, $this->formWidget->getSessionKey());
        });

        Event::fire('wbry.content.pageSaveAfter', [$model, &$delAttr]);
    }
}
