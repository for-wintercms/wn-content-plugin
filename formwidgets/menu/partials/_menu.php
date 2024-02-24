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
                <div class="menu-header"><h3>#1 menu-header</h3><i class="icon-angle-right"></i></div>
                <div class="menu-body">
                    <div class="row menu-item-form">

                        <!-- https://www.w3schools.com/tags/tag_a.asp -->

                        <div class="col-md-2 col-sm-6">
                            <label for="<?= $field->getId('name') ?>">Name</label>
                            <input id="<?= $field->getId('name') ?>" type="text" class="form-control" placeholder="Name" aria-label="Name">
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label for="<?= $field->getId('css_classes') ?>">Css classes</label>
                            <input id="<?= $field->getId('css_classes') ?>" type="text" class="form-control" placeholder="Css class" aria-label="Css class">
                        </div>

                        <div class="col-md-2 col-sm-6 menu-form-button-type-dropdown">
                            <label for="<?= $field->getId('button_type') ?>">Button type</label>
                            <div class="dropdown">
                                <a id="<?= $field->getId('button_type') ?>" href="#" data-toggle="dropdown" class="btn btn-primary wn-icon-angle-down">Button type</a>
                                <ul class="dropdown-menu" role="menu" data-dropdown-title="Button type">
                                    <li role="presentation"><a role="menuitem" tabindex="-1" href="#" class="wn-icon-link">URL</a></li>
                                    <li role="presentation"><a role="menuitem" tabindex="-1" href="#" class="wn-icon-file">Page</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 menu-form-button-type-input">
                            <label for="<?= $field->getId('button_url') ?>">URL</label>
                            <input id="<?= $field->getId('button_url') ?>" type="text" class="form-control" placeholder="Url" aria-label="Url">
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label for="<?= $field->getId('target') ?>">Target attr</label>
                            <select id="<?= $field->getId('target') ?>" class="form-control custom-select">
                                <option value="none" selected="selected">None</option>
                                <option value="blank">_blank</option>
                                <option value="self">_self</option>
                                <option value="parent">_parent</option>
                                <option value="top">_top</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label for="<?= $field->getId('icon') ?>">Icon</label>
                            <select id="<?= $field->getId('icon') ?>" class="form-control custom-select">
                                <option value="1" selected="selected">One</option>
                                <option value="2">Two</option>
                            </select>
                        </div>

                    </div>
                    <label for="subMenu_123456">Sub menu</label>
                    <div class="content-menu">

                        <!-- Menu list -->
                        <div class="menu-item">
                            <div class="menu-header"><h3>#1 menu-header</h3><i class="icon-angle-right"></i></div>
                            <div class="menu-body">
                                FORM
                            </div>
                        </div>
                        <div class="menu-item">
                            <div class="menu-header"><h3>#2 menu-header</h3><i class="icon-angle-right"></i></div>
                            <div class="menu-body">
                                FORM
                            </div>
                        </div>

                        <!-- Add buttons-->
                        <div class="add-btns">
                            <div class="btn-group" role="group" aria-label="Adding a menu">
                                <button type="button" class="btn btn-outline-success wn-icon-add"><?= e(trans('forwintercms.content::widget.menu.add_item')) ?></button>
                                <button type="button" class="btn btn-outline-warning wn-icon-ellipsis-h"><?= e(trans('forwintercms.content::widget.menu.add_separator')) ?></button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="menu-item">
                <div class="menu-header"><h3>#2 menu-header</h3><i class="icon-angle-right"></i></div>
                <div class="menu-body">

                    <div class="content-menu">

                        <!-- Menu list -->
                        <!-- EMPTY -->

                        <!-- Add buttons-->
                        <div class="add-btns">
                            <div class="btn-group" role="group" aria-label="Adding a menu">
                                <button type="button" class="btn btn-outline-success wn-icon-add"><?= e(trans('forwintercms.content::widget.menu.add_item')) ?></button>
                                <button type="button" class="btn btn-outline-warning wn-icon-ellipsis-h"><?= e(trans('forwintercms.content::widget.menu.add_separator')) ?></button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Add buttons-->
            <div class="add-btns">
                <div class="btn-group" role="group" aria-label="Adding a menu">
                    <button type="button" class="btn btn-outline-success wn-icon-add"><?= e(trans('forwintercms.content::widget.menu.add_item')) ?></button>
                    <button type="button" class="btn btn-outline-warning wn-icon-ellipsis-h"><?= e(trans('forwintercms.content::widget.menu.add_separator')) ?></button>
                </div>
            </div>
        </div>

    </div>
<?php endif ?>
