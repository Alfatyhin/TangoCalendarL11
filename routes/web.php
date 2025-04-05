<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SocialController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// роуты
Route::get('/register', function () {
    return response('', 404);
});

Route::get('/', function () {
    return view('calendar_viwer');
})->name('index');

Route::get('/privacy-policy', [IndexController::class, 'privacyPolicy'])
    ->name('privacy-policy');

Route::get('/app/privacy-policy/{lang?}', [IndexController::class, 'appPrivacyPolicy']);

Route::get('/app/delete-user-data', [IndexController::class, 'appDeleteUserData']);

Route::get('/app/users-support/{lang?}', [IndexController::class, 'appUsersSupport'])
    ->name('app_users_support');

Route::get('/user-agreement', [IndexController::class, 'userAgreement'])
    ->name('user-agreement');

Route::get('/user-data-delete', [IndexController::class, 'userAgreement'])
    ->name('user-data-delete');


Route::middleware(['auth:sanctum', 'verified'])->get('/crm', [HomeController::class, 'index'])
    ->name('home');

Route::middleware(['auth:sanctum', 'verified'])->get('/users', [UserController::class, 'allUsers'])
    ->name('users');

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', [UserController::class, 'dashboard'])
    ->name('dashboard');

