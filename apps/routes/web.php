<?php

use App\Http\Controllers\DownloadNotImportedRows;
use App\Livewire\MyReferences;
use App\Livewire\MySpeciesReports;
use App\Livewire\MySuggestions;
use App\Livewire\NisData;
use App\Livewire\NisSpecies;
use App\Livewire\PublicProfile;
use App\Services\ManualPdf;
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

// Public NIS data: the introduction events table, then one page per species.
// 'pages/data' is in layup.frontend.excluded_paths so the CMS catch-all leaves it alone.
Route::get('/pages/data', NisData::class)->name('data');

// The user manual (resources/docs/user-manual.md), public: anonymous visitors read it too.
Route::view('/pages/manual', 'manual')->name('manual');
Route::get('/pages/manual.pdf', fn (ManualPdf $pdf) => $pdf->download('user-manual'))->name('manual.pdf');
// The admin manual describes back-office tools: staff only, like the panel page it comes from.
Route::get('mamias/admin-manual.pdf', fn (ManualPdf $pdf) => $pdf->download('admin-manual'))
    ->middleware(['auth', 'role:super_admin|scientist'])
    ->name('admin-manual.pdf');
Route::get('/pages/data/{introEventRecord}', NisSpecies::class)->name('data.species');

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

// Excel/CSV of the rows an import did not create (skipped duplicates + failures).
// The controller checks that the signed-in user owns the import.
Route::get('mamias/imports/{import}/not-imported-rows.{format}', DownloadNotImportedRows::class)
    ->whereIn('format', ['xlsx', 'csv'])
    ->middleware('auth')
    ->name('imports.not-imported-rows.download');

Route::get('mamias/decompose', [DecomposerController::class, 'index'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('decompose');
