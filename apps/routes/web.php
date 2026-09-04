<?php

use App\Livewire\MyReferences;
use App\Livewire\MySpeciesReports;
use App\Livewire\MySuggestions;
use App\Livewire\PublicProfile;
use Crumbls\Layup\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use Lubusin\Decomposer\Controllers\DecomposerController;

Route::get('/login', function () {
    return redirect()->route('filament.mamias.auth.login');
})->name('login');
Route::get('/email-verification/prompt', function () {
    return redirect()->route('filament.mamias.auth.email-verification.prompt');
})->name('verification.notice');

// Site root serves the Layup "home" page (config: layup.pages.default_slug = 'home').
// Named 'home' so the shared layout, navbar, and breadcrumbs can resolve route('home').
Route::get('/', PageController::class)->name('home');

// Named 'about' for the navbar/breadcrumbs; served by the Layup "about" page.
Route::get('/about', PageController::class)
    ->defaults('slug', 'about')
    ->name('about');

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
