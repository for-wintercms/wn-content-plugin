<?php
/**
 * @var $this ForWinterCms\Content\FormWidgets\Menu Menu form widget
 * @var $field Backend\Classes\FormField Object containing general form field information.
 */

$fieldName = $field->getName();
?>
<!-- Menu List -->
<?php if ($this->previewMode || $field->readOnly || $field->disabled): ?>
    PreviewMode!
<?php else: ?>
    <div
            id="<?= $field->getId() ?>"
            class="field-contentmenu"
        <?= $field->getAttributes() ?>>

        <div class="content-menu">

            <!-- Menu list -->
            <div class="menu-item">
                <div class="menu-header"><h3>#1 menu-header</h3><i class="icon-angle-down"></i></div>
                <div class="menu-body">
                    FORM
                </div>
            </div>
            <div class="menu-item">
                <div class="menu-header"><h3>#2 menu-header</h3><i class="icon-angle-down"></i></div>
                <div class="menu-body">
                    FORM
                </div>
            </div>

            <!-- Add buttons-->
            <br>
            <div class="btn-group" role="group" aria-label="Adding a menu">
                <button type="button" class="btn btn-outline-success wn-icon-add"><?= e(trans('forwintercms.content::widget.menu.add_item')) ?></button>
                <button type="button" class="btn btn-outline-warning wn-icon-ellipsis-h"><?= e(trans('forwintercms.content::widget.menu.add_separator')) ?></button>
            </div>
        </div>

    </div>
<?php endif ?>
