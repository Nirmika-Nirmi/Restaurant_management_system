<?php

return [

    // matrix columns
    'columns' => [
        'view'   => 'View',
        'create' => 'Create',
        'edit'   => 'Edit',
        'delete' => 'Delete',
        'manage' => 'Manage',
        'status' => 'Status update',
    ],

    // permission name = module.action   (example: menu.edit)
    'modules' => [
        'dashboard'     => ['label' => 'Dashboard',        'actions' => ['view']],
        'menu'          => ['label' => 'Menu & categories', 'actions' => ['view', 'create', 'edit', 'delete']],
        'combos'        => ['label' => 'Combos',           'actions' => ['view', 'create', 'edit', 'delete']],
        'orders'        => ['label' => 'Orders',           'actions' => ['view', 'manage', 'status']],
        'reservations'  => ['label' => 'Reservations',     'actions' => ['view', 'manage', 'status']],
        'customers'     => ['label' => 'Customers',        'actions' => ['view', 'manage']],
        'reviews'       => ['label' => 'Reviews',          'actions' => ['view', 'manage']],
        'coupons'       => ['label' => 'Coupons',          'actions' => ['view', 'create', 'edit', 'delete']],
        'wheel'         => ['label' => 'Lucky wheel',      'actions' => ['view', 'manage']],
        'delivery'      => ['label' => 'Delivery zones',   'actions' => ['view', 'manage']],
        'payments'      => ['label' => 'Payments',         'actions' => ['view', 'manage']],
        'notifications' => ['label' => 'Notifications',    'actions' => ['view', 'manage']],
        'banners'       => ['label' => 'Banners',          'actions' => ['view', 'manage']],
        'messages'      => ['label' => 'Contact messages', 'actions' => ['view', 'manage']],
        'settings'      => ['label' => 'Restaurant settings', 'actions' => ['view', 'manage']],
        'reports'       => ['label' => 'Reports',          'actions' => ['view']],
        'users'         => ['label' => 'Admin users',      'actions' => ['view', 'create', 'edit', 'delete']],
        'audit'         => ['label' => 'Audit logs',       'actions' => ['view']],
    ],
];