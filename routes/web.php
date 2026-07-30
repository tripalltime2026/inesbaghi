<?php

use App\Http\Controllers\Admin\AdmissionController as AdminAdmissionController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\BillingController as AdminBillingController;
use App\Http\Controllers\Admin\ChildController as AdminChildController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\GroupController as AdminGroupController;
use App\Http\Controllers\AdmissionApplicationController;
use App\Http\Controllers\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Parent\ForumController as ParentForumController;
use App\Http\Controllers\PhoneOtpController;
use App\Http\Controllers\PublicContentController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'site')->name('home');

Route::get('/content/public', [PublicContentController::class, 'index'])->name('content.public');
Route::get('/content/items/{item}/image', [PublicContentController::class, 'itemImage'])->name('content.item-image');
Route::get('/content/blog/{post}/cover', [PublicContentController::class, 'blogCover'])->name('content.blog-cover');

Route::post('/admissions', [AdmissionApplicationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('admissions.store');

Route::get('/auth/mode', [PhoneOtpController::class, 'mode'])
    ->name('auth.mode');
Route::post('/auth/demo/login', [PhoneOtpController::class, 'demoLogin'])
    ->middleware('throttle:20,1')
    ->name('auth.demo');
Route::post('/auth/phone/request', [PhoneOtpController::class, 'request'])
    ->middleware('throttle:10,1')
    ->name('auth.request');
Route::post('/auth/phone/verify', [PhoneOtpController::class, 'verify'])
    ->middleware('throttle:10,1')
    ->name('auth.verify');
Route::post('/logout', [PhoneOtpController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/content', [AdminContentController::class, 'index'])->name('content.index');
        Route::put('/content/texts', [AdminContentController::class, 'updateTexts'])->name('content.texts.update');
        Route::post('/content/items/{type}', [AdminContentController::class, 'storeItem'])->name('content.items.store');
        Route::patch('/content/items/{item}', [AdminContentController::class, 'updateItem'])->name('content.items.update');
        Route::delete('/content/items/{item}', [AdminContentController::class, 'destroyItem'])->name('content.items.destroy');
        Route::post('/content/blog', [AdminContentController::class, 'storeBlog'])->name('content.blog.store');
        Route::patch('/content/blog/{post}', [AdminContentController::class, 'updateBlog'])->name('content.blog.update');
        Route::delete('/content/blog/{post}', [AdminContentController::class, 'destroyBlog'])->name('content.blog.destroy');

        Route::get('/admissions', [AdminAdmissionController::class, 'index'])->name('admissions.index');
        Route::get('/admissions/{application}', [AdminAdmissionController::class, 'show'])->name('admissions.show');
        Route::patch('/admissions/{application}', [AdminAdmissionController::class, 'update'])->name('admissions.update');
        Route::post('/admissions/{application}/notes', [AdminAdmissionController::class, 'storeNote'])->name('admissions.notes.store');
        Route::post('/admissions/{application}/convert', [AdminAdmissionController::class, 'convert'])->name('admissions.convert');

        Route::get('/children', [AdminChildController::class, 'index'])->name('children.index');
        Route::get('/children/{child}', [AdminChildController::class, 'show'])->name('children.show');
        Route::patch('/children/{child}', [AdminChildController::class, 'update'])->name('children.update');
        Route::patch('/enrollments/{enrollment}', [AdminEnrollmentController::class, 'update'])->name('enrollments.update');

        Route::get('/groups', [AdminGroupController::class, 'index'])->name('groups.index');
        Route::get('/groups/{group}', [AdminGroupController::class, 'show'])->name('groups.show');
        Route::patch('/groups/{group}', [AdminGroupController::class, 'update'])->name('groups.update');
    });

    Route::middleware('role:admin,finance')->group(function () {
        Route::get('/payments', [AdminBillingController::class, 'index'])->name('payments.index');
        Route::post('/payments/generate', [AdminBillingController::class, 'generate'])->name('payments.generate');
        Route::get('/payments/{payment}', [AdminBillingController::class, 'show'])->name('payments.show');
        Route::patch('/payments/{payment}', [AdminBillingController::class, 'update'])->name('payments.update');
        Route::post('/payments/{payment}/transactions', [AdminBillingController::class, 'storeTransaction'])->name('payments.transactions.store');
    });

    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');
        Route::put('/attendance/{child}', [AdminAttendanceController::class, 'update'])->name('attendance.update');
    });
});

Route::prefix('parent')->name('parent.')->middleware(['auth', 'role:parent'])->group(function () {
    Route::get('/', ParentDashboardController::class)->name('dashboard');
    Route::post('/forum/topics', [ParentForumController::class, 'storeTopic'])
        ->middleware('throttle:10,1')
        ->name('forum.topics.store');
    Route::post('/forum/topics/{topic}/comments', [ParentForumController::class, 'storeComment'])
        ->middleware('throttle:30,1')
        ->name('forum.comments.store');
});
