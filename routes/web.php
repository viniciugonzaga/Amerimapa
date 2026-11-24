<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JogadorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home'); 
})->name('home');

Route::middleware(['auth'])->group(function () {
    // Tela Principal (Dashboard)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Tela de Jogadores
    Route::get('/jogadores', [JogadorController::class, 'index'])->name('jogadores.index');
});

require __DIR__.'/auth.php';