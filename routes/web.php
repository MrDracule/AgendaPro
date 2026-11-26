<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

App::setLocale('pt_BR');

Route::get('/', function () {
    return view('welcome');
});

// --- GRUPO 1: Usuários Logados e Ativos (Acessível por USER e ADMIN) ---
Route::middleware(['auth', 'verified', 'active'])->group(function () {

    // Dashboard Geral
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil (Qualquer usuário logado pode editar o seu próprio)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Agenda de Contatos (Cada um vê o seu)
    // A rota de exportação precisa vir ANTES do resource para evitar conflitos com IDs
    Route::get('/contacts/export/pdf', [ContactController::class, 'exportPdf'])->name('contacts.pdf');
    Route::resource('contacts', ContactController::class);
});

// --- GRUPO 2: Somente Administradores ---
Route::middleware(['auth', 'active', 'is_admin'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users');
    Route::patch('/admin/users/{user}/toggle', [AdminController::class, 'toggleStatus'])->name('admin.users.toggle');
});

require __DIR__.'/auth.php';