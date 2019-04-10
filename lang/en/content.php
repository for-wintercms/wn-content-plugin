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
    'errors' => [
        'content_menu' => 'Correctly declare the menu item in the file :fileName',
        'content_list' => 'Correctly declare the content item in the file :fileName',
        'empty_action' => 'Page not defined.',
        'no_page'      => 'Not found the settings page ":pageSlug"',
        'error_config' => 'Incorrect syntax in configuration file ":fileName"',
        'available_item' => 'The content of the page ":pageSlug" setting ":itemSlug" is already available',
        'add_item_empty_args' => 'Unable to add empty data',
    ],
];