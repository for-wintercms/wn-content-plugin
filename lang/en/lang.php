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
        'rename' => 'Rename',
        'clone'  => 'Clone',
    ],
    'menu' => [
        'items' => 'Content',
    ],
    'components' => [
        'get_content' => [
            'name' => 'Content collector',
            'desc' => 'Assembles a template from the specified finished blocks.',
            'page_slug_title' => 'Slug page URL',
            'page_slug_desc'  => 'The address of the page that will dynamically change',
            'is404_title' => 'Page 404',
            'is404_desc'  => 'If the configuration page is not found, showing 404 page.',
        ],
    ],
];