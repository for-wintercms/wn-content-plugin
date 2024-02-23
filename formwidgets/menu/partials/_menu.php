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

<!--                        https://www.w3schools.com/tags/tag_a.asp -->

                        <div class="col-md-2">
                            <input type="text" class="form-control" placeholder="Name" aria-label="Name">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" placeholder="Css class" aria-label="Css class">
                        </div>
                        <div class="col-md-4">
                            <div class="dropdown menu-form-button-type-dropdown">
                                <a href="#" data-toggle="dropdown" class="btn btn-primary wn-icon-angle-down">Button type</a>

                                <ul class="dropdown-menu" role="menu" data-dropdown-title="Add something small">
                                    <li role="presentation"><a role="menuitem" tabindex="-1" href="#" class="wn-icon-folder">Group</a></li>
                                    <li role="presentation"><a role="menuitem" tabindex="-1" href="#" class="wn-icon-copy">Page</a></li>
                                </ul>
                            </div>
                            <div class="menu-form-button-type-input">
                                <input type="text" class="form-control" placeholder="Url" aria-label="Url">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="checkbox custom-checkbox menu-form-target-blank">
                                <input name="checkbox" value="1" type="checkbox" id="checkbox_1">
                                <label for="checkbox_1">target="_blank"</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control custom-select">
                                <option value="1" selected="selected">One</option>
                                <option value="2">Two</option>
                            </select>
                        </div>

                    </div>
                    <label for="subMenu_123456">Sub menu</label>
                    <div class="content-menu" id="subMenu_123456">

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
