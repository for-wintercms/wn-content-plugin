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
        ],
    ],
    'models' => [
        'item' => [
            'fields' => [
                'title_label'  => 'Title',
                'slug_label'   => 'Slug',
                'items_label'  => 'Content',
                'items_prompt' => 'Add new item',
            ],
        ],
    ],
];