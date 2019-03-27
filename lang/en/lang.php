<?php return [
    'plugin' => [
        'name' => 'Content control',
        'description' => 'Content control manager'
    ],
    'controllers' => [
        'items' => [
            'list_title'   => 'List',
            'create_title' => 'Create item',
            'update_title' => 'Update :title',
            'no_content'   => 'Add control area',
            'errors' => [
                'repeater_menu'    => 'Correctly declare the menu item in the file :fileName',
                'repeater_example' => 'The correct example is:',
            ],
        ],
    ],
];