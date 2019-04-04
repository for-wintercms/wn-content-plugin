<?php return [
    'plugin' => [
        'name' => 'Контент контроль',
        'description' => 'Менеджер управления контентом'
    ],
    'permissions' => [
        'items' => 'Управления контентом',
    ],
    'controllers' => [
        'items' => [
            'list_title'   => 'Список',
            'create_title' => 'Создать контент',
            'update_title' => 'Обновить :title',
            'no_content'   => 'Добавьте область управления',
            'errors' => [
                'repeater_menu'     => 'Правильно объявите пункт меню в файле :fileName',
                'repeater_list'     => 'Правильно объявите пункт репитера в файле :fileName',
                'repeater_example'  => 'Правильный пример:',
                'items_empty'       => 'Выберите шаблон репитера',
                'items_no_repeater' => 'Репитер :repeater не найден',
            ],
        ],
    ],
];