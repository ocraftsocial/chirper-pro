<?php

// routes/web.php

use App\Http\Controllers\ChirpController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Routes for chirps (posts)
Route::resource('chirps', ChirpController::class)
    ->only(['index', 'store', 'edit', 'update'])
    ->middleware(['auth', 'verified']);


    Route::get('/chirps/download/{id}', [ChirpController::class, 'downloadChirpFiles'])->middleware(['auth', 'verified'])->name('chirps.download');
// Single File Donwload
    Route::get('/chirps/download-file/{id}', [ChirpController::class, 'downloadChirpFile'])->name('chirps.downloadFile');
    Route::get('/chirps/image/{id}', [ChirpController::class, 'getImage'])->middleware(['auth', 'verified'])->name('chirps.image');
    require __DIR__.'/auth.php';


