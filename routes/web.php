<?php

use App\Http\Controllers\BucketFileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


// Route::get('/app', function () {
//     return view('app');
// });
// Route::livewire('/post/create', 'pages::post.create');

Route::livewire('/', 'pages::user.home-page')->name("home");
Route::livewire('/menu/{product:slug}', 'pages::user.show-menu-page');
Route::livewire('/menu', 'pages::user.show-all-menu-page');
Route::livewire('/tenants', 'pages::user.show-all-tenant-page');
Route::livewire('/tenant/{tenant:tenant_code}', 'pages::user.show-tenant-page');
Route::livewire('/search', 'pages::user.search-page');
Route::livewire('/cart', 'pages::user.cart-page');


Route::middleware('guest')->group(function () {
    Route::livewire('/register', 'pages::user.register');
    Route::livewire('/login', 'pages::user.login')->name('login');
});


Route::middleware('auth')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::livewire('/checkout', 'pages::user.checkout');
    Route::livewire('/my-order', 'pages::user.my-order');
});

Route::get('/files/{path}', [BucketFileController::class, 'show'])
    ->where('path', '.*')
    ->name('bucket.file');


// Route::middleware(['auth', 'can:access-tenant'])->group(function () {
//     Route::livewire('/tenants/dashboard', 'pages::dashboard.dashboard-tenant')->name('tenant.dashboard');
//     Route::livewire('/tenants/menu', 'pages::dashboard.menu-tenant')->name('tenant.menu');
// });