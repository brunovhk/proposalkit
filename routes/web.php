<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\PublicProposalController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('p/{uuid}', [PublicProposalController::class, 'show'])
    ->name('public.proposals.show');
Route::post('p/{uuid}/transition', [PublicProposalController::class, 'transition'])
    ->name('public.proposals.transition');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('proposals', ProposalController::class)->except('show');
    Route::resource('services', ServiceController::class)->except('show');
    Route::resource('clients', ClientController::class)->except('show');
});

require __DIR__.'/settings.php';
