<?php

return [
    'columns' => [
        'view'   => 'View',
        'create' => 'Create',
        'edit'   => 'Edit',
        'delete' => 'Delete',
        'manage' => 'Manage',
        'status' => 'Status update',
    ],

    'modules' => [
        'dashboard' => ['label' => 'Dashboard',        'actions' => ['view']],
        'menu'      => ['label' => 'Menu & categories', 'actions' => ['view', 'create', 'edit', 'delete']],
        'orders'    => ['label' => 'Orders',           'actions' => ['view', 'manage', 'status']],
        'customers' => ['label' => 'Customers',        'actions' => ['view', 'manage']],
        'users'     => ['label' => 'Admin users',      'actions' => ['view', 'create', 'edit', 'delete']],
        'roles'     => ['label' => 'Roles & permissions', 'actions' => ['view', 'manage']],
        'reports'   => ['label' => 'Reports',          'actions' => ['view']],
    ],
];