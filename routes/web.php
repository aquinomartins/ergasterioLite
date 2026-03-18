<?php

use App\Http\Controllers\Admin\AdminArtistController;
use App\Http\Controllers\Admin\AdminArtworkController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminMarketController;
use App\Http\Controllers\Artists\ArtistController;
use App\Http\Controllers\Artworks\ArtworkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Markets\MarketController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/artists', [ArtistController::class, 'index'])->name('artists.index');
Route::get('/artists/{artist:slug}', [ArtistController::class, 'show'])->name('artists.show');
Route::get('/artworks', [ArtworkController::class, 'index'])->name('artworks.index');
Route::get('/artworks/{artwork:slug}', [ArtworkController::class, 'show'])->name('artworks.show');
Route::get('/markets', [MarketController::class, 'index'])->name('markets.index');
Route::get('/markets/{market:slug}', [MarketController::class, 'show'])->name('markets.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:create,App\Domain\Markets\Models\Market'])->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::resource('artists', AdminArtistController::class)->except(['show', 'destroy']);
    Route::resource('artworks', AdminArtworkController::class)->except(['show', 'destroy']);
    Route::resource('markets', AdminMarketController::class)->except(['show', 'destroy']);
});

require __DIR__.'/auth.php';
