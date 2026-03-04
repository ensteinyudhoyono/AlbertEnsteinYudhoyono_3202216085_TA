<?php

namespace App;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\DashboardRentController;
use App\Http\Controllers\DashboardUserController;
use App\Http\Controllers\DashboardAdminController;
use App\Http\Controllers\DashboardSecurityController;
use App\Http\Controllers\DashboardLoanerController;
use App\Http\Controllers\DashboardRoomController;
use App\Http\Controllers\DashboardItemController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TemporaryRentController;
use App\Http\Controllers\DashboardRentController as ExportDashboardRentController;

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

Route::get('/', function () {
    return view('index', [
        'title' => "Home",
    ]);
});

// Public media proxy for files stored on the 'public' disk (works even if symlink fails on Windows)
Route::get('/media/{path}', function (string $path) {
    $decodedPath = urldecode($path);
    if (!Storage::disk('public')->exists($decodedPath)) {
        abort(404);
    }
    $contents = Storage::disk('public')->get($decodedPath);
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($contents) ?: 'application/octet-stream';
    return response($contents, 200)
        ->header('Content-Type', $mime)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('path', '.*')->name('media.public');

Route::get('/login', [LoginController::class, 'index'])->name('login')->middleware('guest');

Route::post('/login', [LoginController::class, 'authenticate']);

// Route::get('/register', [RegisterController::class, 'index'])->name('register')->middleware('guest');

// Route::post('/register', [RegisterController::class, 'store']);

Route::post('/check-email', [RegisterController::class, 'checkEmail']);

Route::post('/logout', [LoginController::class, 'logout']);

Route::get('dashboard/rents/{id}/endTransaction', [DashboardRentController::class, 'endTransaction'])->middleware('auth');

Route::resource('dashboard/rents', DashboardRentController::class)->middleware('auth');
// Export rents to Excel
Route::get('dashboard/rents-export', [ExportDashboardRentController::class, 'exportExcel'])->middleware('auth')->name('rents.export');

Route::get('/dashboard', [DashboardRoomController::class, 'index'])->middleware('auth');

Route::resource('dashboard/rooms', DashboardRoomController::class)->middleware('auth');

Route::resource('dashboard/items', DashboardItemController::class)->middleware('auth');

Route::get('dashboard/users/{id}/makeSecurity', [DashboardUserController::class, 'makeSecurity'])->middleware('auth');

Route::get('dashboard/users/{id}/toggleStatus', [DashboardUserController::class, 'toggleStatus'])->middleware('auth');

Route::resource('dashboard/users', DashboardUserController::class)->middleware('auth');

Route::get('dashboard/admin/{id}/removeSecurity', [DashboardAdminController::class, 'removeSecurity'])->middleware('auth');

Route::resource('dashboard/admin', DashboardAdminController::class)->middleware('auth');

Route::resource('dashboard/security', DashboardSecurityController::class)->middleware('auth');

Route::resource('dashboard/loaner', DashboardLoanerController::class)->middleware('auth');

Route::get('/dashboard/temporaryRents', [TemporaryRentController::class, 'index'])->middleware('auth');

Route::post('/dashboard/temporaryRents/{id}/acceptRents', [TemporaryRentController::class, 'acceptRents'])->middleware('auth');

Route::post('/dashboard/temporaryRents/{id}/declineRents', [TemporaryRentController::class, 'declineRents'])->middleware('auth');

Route::get('/help', function () {
    return view('help', [
        'title' => "Help",
    ]);
});

Route::get('/about', function () {
    return view('about', [
        'title' => "About"
    ]);
});
