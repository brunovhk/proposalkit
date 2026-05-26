<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\PublicProposalController;
use App\Http\Controllers\ServiceController;
use App\Http\Middleware\BlockDemoWriteOperations;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'marketing/Index')->name('home');
Route::post('demo/login', DemoLoginController::class)->name('demo.login');

Route::middleware('signed')->group(function (): void {
    Route::get('p/{tenant_slug}/{secure_token}', [PublicProposalController::class, 'show'])
        ->name('public.proposals.show');
    Route::get('p/{tenant_slug}/{secure_token}/pdf', [PublicProposalController::class, 'pdf'])
        ->name('public.proposals.pdf');
    Route::post('p/{tenant_slug}/{secure_token}/accept', [PublicProposalController::class, 'accept'])
        ->name('public.proposals.accept');
    Route::post('p/{tenant_slug}/{secure_token}/reject', [PublicProposalController::class, 'reject'])
        ->name('public.proposals.reject');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware(BlockDemoWriteOperations::class)->group(function (): void {
        Route::post('proposals/{proposal}/send', [ProposalController::class, 'markAsSent'])
            ->name('proposals.send');
        Route::resource('proposals', ProposalController::class)->except('show');
        Route::resource('services', ServiceController::class)->except('show');
        Route::resource('clients', ClientController::class)->except('show');
    });
});

require __DIR__.'/settings.php';
