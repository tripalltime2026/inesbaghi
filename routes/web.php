<?php

use App\Http\Controllers\Admin\AdmissionController as AdminAdmissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AdmissionApplicationController;
use App\Http\Controllers\PhoneOtpController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'site')->name('home');

Route::post('/admissions', [AdmissionApplicationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('admissions.store');

Route::post('/auth/phone/request', [PhoneOtpController::class, 'request'])
    ->middleware('throttle:10,1')
    ->name('auth.request');
Route::post('/auth/phone/verify', [PhoneOtpController::class, 'verify'])
    ->middleware('throttle:10,1')
    ->name('auth.verify');
Route::post('/logout', [PhoneOtpController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/admissions', [AdminAdmissionController::class, 'index'])->name('admissions.index');
    Route::get('/admissions/{application}', [AdminAdmissionController::class, 'show'])->name('admissions.show');
    Route::patch('/admissions/{application}', [AdminAdmissionController::class, 'update'])->name('admissions.update');
    Route::post('/admissions/{application}/notes', [AdminAdmissionController::class, 'storeNote'])->name('admissions.notes.store');
    Route::post('/admissions/{application}/convert', [AdminAdmissionController::class, 'convert'])->name('admissions.convert');
});
