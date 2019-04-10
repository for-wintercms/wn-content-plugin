<?php return [
    'plugin' => [
        'name' => 'Content control',
        'description' => 'Content control manager'
    ],
    'permissions' => [
        'items'         => 'Content management',
        'items_changes' => 'Content block management (for developers only)',
    ],
    'controllers' => [
        'items' => [
            'list_title'   => 'List',
            'create_title' => 'Create content',
            'update_title' => 'Update - :title [:name]',
            'no_content'   => 'Add control area',
            'errors' => [
                'repeater_example'  => 'The correct example is:',
                'items_empty'       => 'Choose a repeater pattern',
                'items_no_repeater' => 'Repeater :repeater not found',
            ],
        ],
    ],
];