<?php
return [
    // Servicios
    'menu' => function($container) {
        return new App\Services\MenuService(
            $container['db'],
            $container['logger']
        );
    },
    
    // Controladores
    'MenuController' => function($container) {
        return new App\Controllers\MenuController(
            $container['menu']
        );
    },
];