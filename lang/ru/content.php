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
    'list' => [
        'popup_btn_new_item'   => 'Новый шаблон',
        'popup_btn_ready_item' => 'Готовый шаблон',
        'form_ready_tmp_label' => 'Шаблон',
        'form_ready_tmp_empty' => 'Готовые шаблоны отсутствуют',
    ],
    'errors' => [
        'content_menu' => 'Правильно объявите пункт меню в файле :fileName',
        'content_list' => 'Правильно объявите пункт контента в файле :fileName',
        'error_config' => 'Неверный синтаксис в конфигурационном файле ":fileName"',
        'empty_action' => 'Страница не определена.',
        'no_page'      => 'Не найдена страница настроек ":pageSlug"',
        'no_item_tmp'  => 'Шаблон ":itemSlug" не найден',
        'no_exists_item' => 'Контент блок ":itemSlug" уже существует',
        'available_item' => 'В контент странице ":pageSlug" настройка ":itemSlug" уже имеется',
        'add_item_empty_args' => 'Невозможно добавить пустые данные',
    ],
    'success' => [
        'create_item' => 'Контент блок ":itemName" успешно добавлен',
    ],
];