<?php return [
    'plugin' => [
        'name' => 'Content control',
        'description' => 'Content control manager'
    ],
    'permissions' => [
        'items'         => 'Content management',
        'items_changes' => 'Content block management (for developers only)',
    ],
    'form' => [
        'change' => 'Change',
    ],
    'controllers' => [
        'items' => [
            'no_content'   => 'Add control area',
            'errors' => [
                'repeater_example'  => 'The correct example is:',
                'items_empty'       => 'Choose a repeater pattern',
                'items_no_repeater' => 'Repeater :repeater not found',
            ],
        ],
    ],
];