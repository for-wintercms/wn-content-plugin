<?php return [
    'plugin' => [
        'name' => 'Content control',
        'description' => 'Content control manager'
    ],
    'permissions' => [
        'items' => 'Content manage',
    ],
    'controllers' => [
        'items' => [
            'list_title'   => 'List',
            'create_title' => 'Create content',
            'update_title' => 'Update - :title [:name]',
            'no_content'   => 'Add control area',
            'errors' => [
                'repeater_menu'     => 'Correctly declare the menu item in the file :fileName',
                'repeater_list'     => 'Correctly declare the repeat item in the file :fileName',
                'repeater_example'  => 'The correct example is:',
                'items_empty'       => 'Choose a repeater pattern',
                'items_no_repeater' => 'Repeater :repeater not found',
            ],
        ],
    ],
];