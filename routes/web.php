<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\ImporController;
use App\Http\Controllers\IndikatorController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/keluarga', [FamilyController::class, 'index'])->name('families.index');
    Route::get('/keluarga/tambah', [FamilyController::class, 'create'])->name('families.create');
    Route::post('/keluarga', [FamilyController::class, 'store'])->name('families.store');
    Route::get('/keluarga/{family}/ubah', [FamilyController::class, 'edit'])->name('families.edit');
    Route::put('/keluarga/{family}', [FamilyController::class, 'update'])->name('families.update');
    Route::delete('/keluarga/{family}', [FamilyController::class, 'destroy'])->name('families.destroy');

    Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
    Route::get('/indikator', [IndikatorController::class, 'index'])->name('indikator.index');

    Route::get('/impor', [ImporController::class, 'index'])->name('impor.index');
    Route::post('/impor', [ImporController::class, 'store'])->name('impor.store');

    Route::get('/ai', [AiController::class, 'index'])->name('ai.index');
    Route::post('/ai/ekstrak', [AiController::class, 'extract'])->name('ai.extract');

    Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    Route::get('/pengguna/tambah', [UserController::class, 'create'])->name('users.create');
    Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
    Route::get('/pengguna/{user}/ubah', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/wilayah', [WilayahController::class, 'index'])->name('wilayah.index');
    Route::post('/wilayah', [WilayahController::class, 'store'])->name('wilayah.store');
    Route::delete('/wilayah', [WilayahController::class, 'destroy'])->name('wilayah.destroy');
});
