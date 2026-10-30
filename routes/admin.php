<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Config\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;

Route::prefix('admin')->name('admin.')->group(function () {

    // ---------- Guest only ----------
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.post');
    });

    // ---------- Logged in only ----------
    Route::middleware('auth:admin')->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('home', [DashboardController::class, 'home'])->name('home');

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.view,admin')->name('dashboard');

        // Menu
        Route::get('menu', [MenuController::class, 'index'])
            ->middleware('permission:menu.view,admin')->name('menu');
        Route::post('menu', [MenuController::class, 'store'])
            ->middleware('permission:menu.create,admin')->name('menu.store');
        Route::get('menu/{item}/edit', [MenuController::class, 'edit'])
            ->middleware('permission:menu.edit,admin')->name('menu.edit');
        Route::put('menu/{item}', [MenuController::class, 'update'])
            ->middleware('permission:menu.edit,admin')->name('menu.update');
        Route::post('menu/{item}/toggle', [MenuController::class, 'toggle'])
            ->middleware('permission:menu.edit,admin')->name('menu.toggle');
        Route::delete('menu/{item}', [MenuController::class, 'destroy'])
            ->middleware('permission:menu.delete,admin')->name('menu.destroy');

        // Categories
        Route::post('categories', [CategoryController::class, 'store'])
            ->middleware('permission:menu.create,admin')->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])
            ->middleware('permission:menu.edit,admin')->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
            ->middleware('permission:menu.delete,admin')->name('categories.destroy');

        // Customers
        Route::get('customers', [CustomerController::class, 'index'])
            ->middleware('permission:customers.view,admin')->name('customers');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])
            ->middleware('permission:customers.view,admin')->name('customers.show');
        Route::post('customers/{customer}/toggle', [CustomerController::class, 'toggle'])
            ->middleware('permission:customers.manage,admin')->name('customers.toggle');

        // ---------- Admin users ----------
        Route::get('users', [UserController::class, 'index'])
            ->middleware('permission:users.view,admin')->name('users');
        Route::post('users', [UserController::class, 'store'])
            ->middleware('permission:users.create,admin')->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])
            ->middleware('permission:users.edit,admin')->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])
            ->middleware('permission:users.edit,admin')->name('users.update');
        Route::post('users/{user}/toggle', [UserController::class, 'toggle'])
            ->middleware('permission:users.edit,admin')->name('users.toggle');
        Route::delete('users/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:users.delete,admin')->name('users.destroy');

        // ---------- Roles & permissions (super admin only) ----------
        Route::middleware('role:super_admin,admin')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles');
            Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });
    });
});