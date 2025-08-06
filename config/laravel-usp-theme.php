<?php

$admin = [
    [
        'text' => '<i class="fas fa-atom"></i>  SubItem 1',
        'url' => 'subitem1',
    ],
    [
        'text' => 'SubItem 2',
        'url' =>  '/subitem2',
        'can' => 'admin',
    ],
    [
        'type' => 'divider',
    ],
    [
        'type' => 'header',
        'text' => 'Cabeçalho',
    ],
    [
        'text' => 'SubItem 3',
        'url' => 'subitem3',
    ],
];

$submenu2 = [
    [
        'text' => 'SubItem 1',
        'url' => 'subitem1',
    ],
    [
        'text' => 'SubItem 2',
        'url' => 'subitem2',
        'can' => 'admin',
    ],
];

$menu = [
    [
        'text' => '<i class="fas fa-home"></i> Home',
        'url' => 'workflows',
    ],
    [
        'text' => 'Novo requerimento',
        'url' => 'workflows/viewcreateobject',
        'can' => 'user',
    ],
    [
        'text' => 'Meus requerimentos',
        'url' => 'workflows/showuserobjects',
        'can' => 'user',
    ],
    [
        'text' => 'Atendimentos',
        'url' => 'workflows/atendimentos',
        'can' => 'user',
    ],
];

$right_menu = [
    [
        'text' => '<span class="text-danger">Formulários</span>',
        'url' => 'uspdev-forms/form-definitions',
        'can' => 'admin'
    ],
    [
        'text' => '<span class="text-danger">Workflows</span>',
        'can' => 'admin',
        'url' => 'workflows/listdefinitions'
    ],
    [
        // menu utilizado para views da biblioteca senhaunica-socialite.
        'key' => 'senhaunica-socialite',
    ],
    [
        'key' => 'laravel-tools',
    ],
];


return [
    # valor default para a tag title, dentro da section title.
    # valor pode ser substituido pela aplicação.
    'title' => config('app.name'),

    # USP_THEME_SKIN deve ser colocado no .env da aplicação
    'skin' => env('USP_THEME_SKIN', 'uspdev'),

    # chave da sessão. Troque em caso de colisão com outra variável de sessão.
    'session_key' => 'laravel-usp-theme',

    # usado na tag base, permite usar caminhos relativos nos menus e demais elementos html
    # na versão 1 era dashboard_url
    'app_url' => config('app.url'),

    # login e logout
    'logout_method' => 'POST',
    'logout_url' => 'logout',
    'login_url' => 'login',

    # menus
    'menu' => $menu,
    'right_menu' => $right_menu,

    # mensagens flash - https://uspdev.github.io/laravel#31-mensagens-flash
    'mensagensFlash' => true,

    # container ou container-fluid
    'container' => 'container-fluid',
];
