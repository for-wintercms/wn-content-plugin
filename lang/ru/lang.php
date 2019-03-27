<?php return [
    'plugin' => [
        'name' => 'Content control',
        'description' => 'Content control manager'
    ],
    'controllers' => [
        'items' => [
            'list_title'   => 'Список',
            'create_title' => 'Создать предмет',
            'update_title' => 'Обновить :title',
            'no_content'   => 'Добавьте область управления',
        ],
    ],
    'models' => [
        'item' => [
            'fields' => [
                'title_label'  => 'Название',
                'slug_label'   => 'Slug',
                'items_label'  => 'Контент',
                'items_prompt' => 'Добавить новый элемент',
            ],
        ],
    ],
];