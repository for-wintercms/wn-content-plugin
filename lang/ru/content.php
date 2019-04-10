<?php return [
    'prompt' => 'Добавить новый элемент',
    'items'  => [
        'title_label' => 'Название',
        'name_label'  => 'Ключ',
        'name_cmt'    => 'Имя по которому будете получать контент. Допустимые символы: a-z_-',
        'items_label' => 'Контент',
        'create_at'   => 'Создано',
        'updated_at'  => 'Обновлено',
    ],
    'errors' => [
        'content_menu' => 'Правильно объявите пункт меню в файле :fileName',
        'content_list' => 'Правильно объявите пункт контента в файле :fileName',
        'empty_action' => 'Страница не определена.',
        'no_page'      => 'Не найдена страница настроек ":pageSlug"',
        'error_config' => 'Неверный синтаксис в конфигурационном файле ":fileName"',
        'available_item' => 'В контент странице ":pageSlug" настройка ":itemSlug" уже имеется',
        'add_item_empty_args' => 'Невозможно добавить пустые данные',
    ],
];