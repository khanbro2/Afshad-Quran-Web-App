<?php

use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\BroadThemeController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\AyahThemeController;
use App\Http\Controllers\AyahController;
use App\Http\Controllers\SurahController;
use App\Http\Controllers\TafseerDashboardController;
use App\Http\Controllers\WordController;
use Illuminate\Support\Facades\Route;


Route::redirect('/', '/surahs');

Route::get('/ayah-search', [AyahController::class, 'search'])->name('ayahs.search');
Route::get('/tafaseer-dashboard', [TafseerDashboardController::class, 'index'])->name('tafaseer.dashboard');
Route::get('/ayah-themes', [AyahThemeController::class, 'index'])->name('ayah-themes.index');
Route::get('/ayah-themes/{ayahTheme}', [AyahThemeController::class, 'show'])->name('ayah-themes.show');
Route::get('/broad-themes', [BroadThemeController::class, 'index'])->name('broad-themes.index');
Route::get('/broad-themes/{broadTheme}', [BroadThemeController::class, 'show'])->name('broad-themes.show');

Route::scopeBindings()->group(function () {
    Route::get('/surahs', [SurahController::class, 'index'])->name('surahs.index');
    Route::get('/surahs/{surah}', [SurahController::class, 'show'])->name('surahs.show');
    Route::get('/surahs/{surah}/ayahs/{ayah}', [AyahController::class, 'show'])->name('ayahs.show');
    Route::get('/surahs/{surah}/ayahs/{ayah}/share-card', [AyahController::class, 'shareCard'])->name('ayahs.share-card');
    Route::post('/surahs/{surah}/ayahs/{ayah}/feedback', [AyahController::class, 'storeFeedback'])->name('ayahs.feedback.store');
    Route::post('/surahs/{surah}/ayahs/{ayah}/favorite', [BookmarkController::class, 'toggleAyah'])->name('favorites.ayah.toggle');
    Route::post('/surahs/{surah}/ayahs/{ayah}/notes', [NoteController::class, 'storeAyah'])->name('notes.ayah.store');
});


Route::get('/words/{word}', [WordController::class, 'show'])->name('words.show');
Route::post('/words/{word}/favorite', [BookmarkController::class, 'toggleWord'])->name('favorites.word.toggle');
Route::post('/words/{word}/notes', [NoteController::class, 'storeWord'])->name('notes.word.store');
Route::get('/favorites', [BookmarkController::class, 'index'])->name('favorites.index');
Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
