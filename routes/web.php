<?php
use App\Http\Controllers\AdmissionApplicationController;
use App\Http\Controllers\PhoneOtpController;
use App\Models\AdmissionApplication;
use App\Models\User;
use Illuminate\Support\Facades\Route;
Route::view('/', 'site')->name('home');
Route::post('/admissions', [AdmissionApplicationController::class, 'store'])->middleware('throttle:10,1')->name('admissions.store');
Route::post('/auth/phone/request', [PhoneOtpController::class, 'request'])->middleware('throttle:10,1')->name('auth.request');
Route::post('/auth/phone/verify', [PhoneOtpController::class, 'verify'])->middleware('throttle:10,1')->name('auth.verify');
Route::post('/logout', [PhoneOtpController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/admin', fn () => view('admin.dashboard', [
    'users' => User::count(),
    'pending' => User::where('status', 'pending')->count(),
    'applications' => AdmissionApplication::count(),
]))->middleware(['auth', 'role:admin'])->name('admin');
