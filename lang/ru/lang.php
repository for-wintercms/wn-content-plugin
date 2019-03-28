<?php return [
    'plugin' => [
        'name' => 'Content control',
        'description' => 'Content control manager'
    ],
    'permissions' => [
        'items' => 'Управления контентом',
    ],
    'controllers' => [
        'items' => [
            'list_title'   => 'Список',
            'create_title' => 'Создать предмет',
            'update_title' => 'Обновить :title',
            'no_content'   => 'Добавьте область управления',
            'errors' => [
                'repeater_menu'    => 'Правильно объявите пункт меню в файле :fileName',
                'repeater_list'    => 'Правильно объявите пункт репитора в файле :fileName',
                'repeater_example' => 'Правильный пример:',
            ],
        ],
    ],
];