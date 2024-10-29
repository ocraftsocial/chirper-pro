<?php

// routes/web.php

use App\Http\Controllers\TasksController;
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


// Routes for Tasks (tasks etc...)
// Route::get('/tasks', [TasksController::class, 'index'])->middleware(['auth', 'verified'])->name('tasks.index');
Route::resource('tasks', TasksController::class);
Route::post('tasks/reset', [TasksController::class, 'reset'])->name('tasks.reset');
Route::patch('tasks/{task}/toggle', [TasksController::class, 'toggle'])->name('tasks.toggle');

// Routes for chirps (posts)
Route::resource('chirps', ChirpController::class)
    ->only(['index', 'store', 'edit', 'update'])
    ->middleware(['auth', 'verified']);


    Route::get('/chirps/download/{id}', [ChirpController::class, 'downloadChirpFiles'])->middleware(['auth', 'verified'])->name('chirps.download');
// Single File Donwload
    Route::get('/chirps/download-file/{id}/{file}', [ChirpController::class, 'downloadChirpFile'])->middleware(['auth', 'verified'])->name('chirps.downloadFile');
    Route::post('/chirps/{id}/share', [ChirpController::class, 'shareFile'])->middleware(['auth', 'verified']);
    Route::get('/chirps/share/{token}', [ChirpController::class, 'showSharedChirp'])->name('chirps.shared');
    Route::get('/chirps/shared/download-file/{token}/{file}', [ChirpController::class, 'downloadSharedChirpFile'])
    ->name('chirps.shared.downloadFile');
    Route::get('/chirps/shared/download/{token}', [ChirpController::class, 'downloadSharedChirpFiles'])
    ->name('chirps.shared.download');

// Add this route to the existing chirps routes
    Route::get('/chirps/image/{id}/{file}', [ChirpController::class, 'getImage'])->name('chirps.image');
    // ->middleware(['auth', 'verified'])
    require __DIR__.'/auth.php';


