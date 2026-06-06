<?php

use App\Livewire\MyReferences;
use App\Livewire\MySpeciesReports;
use App\Livewire\MySuggestions;
use App\Livewire\PublicProfile;
use Illuminate\Support\Facades\Route;
use Lubusin\Decomposer\Controllers\DecomposerController;

Route::get('/login', function () {
    return redirect()->route('filament.mamias.auth.login');
})->name('login');
Route::get('/email-verification/prompt', function () {
    return redirect()->route('filament.mamias.auth.email-verification.prompt');
})->name('verification.notice');

Route::get('/', function () {
    return view('mamias.home');
})->name('home');

Route::get('/about', function () {
    return view('mamias.about');
})->name('about');

Route::get('/profile', PublicProfile::class)
    ->middleware(['auth', 'verified'])
    ->name('profile');

Route::get('/references', MyReferences::class)
    ->middleware(['auth', 'verified'])
    ->name('references');

Route::get('/my-species-reports', MySpeciesReports::class)
    ->middleware(['auth', 'verified'])
    ->name('my-species-reports');

Route::get('/my-suggestions', MySuggestions::class)
    ->middleware(['auth', 'verified'])
    ->name('suggestions');

Route::get('mamias/decompose', [DecomposerController::class, 'index'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('decompose');
