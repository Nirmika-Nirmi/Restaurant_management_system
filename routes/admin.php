<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Config\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;

Route::prefix('admin')->name('admin.')->group(function () {

    //   Guest only  
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.post');
    });

    //   Logged in only  
    Route::middleware('auth:admin')->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Menu
        Route::get('menu', [MenuController::class, 'index'])->name('menu');
        Route::post('menu', [MenuController::class, 'store'])->name('menu.store');
        Route::get('menu/{item}/edit', [MenuController::class, 'edit'])->name('menu.edit');
        Route::put('menu/{item}', [MenuController::class, 'update'])->name('menu.update');
        Route::post('menu/{item}/toggle', [MenuController::class, 'toggle'])->name('menu.toggle');
        Route::delete('menu/{item}', [MenuController::class, 'destroy'])->name('menu.destroy');

        // Categories
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Customers
        Route::get('customers', [CustomerController::class, 'index'])->name('customers');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('customers/{customer}/toggle', [CustomerController::class, 'toggle'])->name('customers.toggle');
    });
});