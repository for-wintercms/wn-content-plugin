<?php return [
    'prompt' => 'Add new item',
    'items' => [
        'title_label' => 'Name',
        'name_label'  => 'Key',
        'name_cmt'    => 'The name by which you will receive the content. Valid characters: a-z_-',
        'items_label' => 'Content',
        'create_at'   => 'Created',
        'updated_at'  => 'Updated',
    ],
    'list' => [
        'popup_btn_new_item'   => 'New template',
        'popup_btn_ready_item' => 'Ready template',
        'form_ready_tmp_label' => 'Template',
        'form_ready_tmp_empty' => 'No pre-made templates',
    ],
    'errors' => [
        'content_menu' => 'Correctly declare the menu item in the file :fileName',
        'content_list' => 'Correctly declare the content item in the file :fileName',
        'error_config' => 'Incorrect syntax in configuration file ":fileName"',
        'empty_action' => 'Page not defined.',
        'no_page'      => 'Not found the settings page ":pageSlug"',
        'no_item_tmp'  => 'Template ":itemSlug" not found',
        'no_exists_item' => 'Content block ":itemSlug" already exists',
        'available_item' => 'The content of the page ":pageSlug" setting ":itemSlug" is already available',
        'add_item_empty_args' => 'Unable to add empty data',
    ],
    'success' => [
        'create_item' => 'Content block ":itemName" successfully added',
    ],
];