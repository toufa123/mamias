<?php

    use App\Livewire\PublicProfile;
    use Illuminate\Support\Facades\Route;
    use Lubusin\Decomposer\Controllers\DecomposerController;

    Route::get('/', function () {
        return view('mamias.home');
    })->name('home');

    Route::get('/about', function () {
        return view('mamias.about');
    })->name('about');

    Route::get('/profile', PublicProfile::class)
        ->middleware(['auth', 'verified'])
        ->name('profile');

    Route::get('mamias/decompose', [DecomposerController::class, 'index'])->name('decompose');
