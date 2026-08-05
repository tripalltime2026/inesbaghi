<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdmissionController as AdminAdmissionController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\BillingController as AdminBillingController;
use App\Http\Controllers\Admin\ChildController as AdminChildController;
use App\Http\Controllers\Admin\ClubController as AdminClubController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\GroupController as AdminGroupController;
use App\Http\Controllers\Admin\PrivacyController as AdminPrivacyController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\UserRegistryController as AdminUserRegistryController;
use App\Http\Controllers\AdmissionApplicationController;
use App\Http\Controllers\CredentialsAuthController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\Parent\ClubController as ParentClubController;
use App\Http\Controllers\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Parent\ForumController as ParentForumController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\PublicSeoController;
use App\Http\Controllers\SupportChatController;
use App\Http\Middleware\NoIndexPrivateArea;
use App\Http\Middleware\PublicSeo;
use Illuminate\Support\Facades\Route;

Route::middleware(PublicSeo::class)->group(function () {
    Route::get('/', [PublicSeoController::class, 'home'])->name('home');
    Route::get('/chven-shesakheb', [PublicSeoController::class, 'show'])->defaults('page', 'about')->name('public.about');
    Route::get('/metodologia', [PublicSeoController::class, 'show'])->defaults('page', 'methodology')->name('public.methodology');
    Route::get('/jgufebi', [PublicSeoController::class, 'show'])->defaults('page', 'groups')->name('public.groups');
    Route::get('/gundi', [PublicSeoController::class, 'show'])->defaults('page', 'team')->name('public.team');
    Route::get('/blogi', [PublicSeoController::class, 'show'])->defaults('page', 'blog')->name('public.blog');
    Route::get('/kitkhva-pasukhi', [PublicSeoController::class, 'show'])->defaults('page', 'faq')->name('public.faq');
    Route::get('/kontakti', [PublicSeoController::class, 'show'])->defaults('page', 'contact')->name('public.contact');
    Route::get('/charetskhva', [PublicSeoController::class, 'show'])->defaults('page', 'admission')->name('public.admission');
});
Route::get('/sitemap.xml', [PublicSeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PublicSeoController::class, 'robots'])->name('robots');

Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
Route::get('/privacy/request', [LegalController::class, 'requestForm'])->name('privacy.request');
Route::post('/privacy/request', [LegalController::class, 'storeRequest'])
    ->middleware('throttle:10,1')
    ->name('privacy.request.store');

Route::get('/content/public', [PublicContentController::class, 'index'])->name('content.public');
Route::get('/content/items/{item}/image', [PublicContentController::class, 'itemImage'])->name('content.item-image');
Route::get('/content/blog/{post}/cover', [PublicContentController::class, 'blogCover'])->name('content.blog-cover');

Route::post('/admissions', [AdmissionApplicationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('admissions.store');

Route::prefix('support/chat')->name('support.chat.')->group(function () {
    Route::get('/bootstrap', [SupportChatController::class, 'bootstrap'])
        ->middleware('throttle:60,1')
        ->name('bootstrap');
    Route::post('/conversations', [SupportChatController::class, 'storeConversation'])
        ->middleware('throttle:20,1')
        ->name('conversations.store');
    Route::get('/conversations/{conversation}', [SupportChatController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('conversations.show');
    Route::post('/conversations/{conversation}/messages', [SupportChatController::class, 'storeMessage'])
        ->middleware('throttle:30,1')
        ->name('messages.store');
    Route::post('/conversations/{conversation}/human', [SupportChatController::class, 'requestHuman'])
        ->middleware('throttle:10,1')
        ->name('human');
    Route::patch('/conversations/{conversation}/contact', [SupportChatController::class, 'updateContact'])
        ->middleware('throttle:10,1')
        ->name('contact.update');
});

Route::middleware(NoIndexPrivateArea::class)->group(function () {
    Route::get('/shesvla', [CredentialsAuthController::class, 'showLogin'])
        ->name('auth.credentials.login.form');
    Route::post('/shesvla', [CredentialsAuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('auth.credentials.login');
    Route::get('/registratsia', [CredentialsAuthController::class, 'showRegister'])
        ->name('auth.credentials.register.form');
    Route::post('/registratsia', [CredentialsAuthController::class, 'register'])
        ->middleware('throttle:6,1')
        ->name('auth.credentials.register');
});

Route::get('/auth/mode', [CredentialsAuthController::class, 'mode'])->name('auth.mode');
Route::post('/auth/demo/login', [CredentialsAuthController::class, 'unavailable'])
    ->middleware('throttle:10,1')
    ->name('auth.demo');
Route::post('/auth/phone/request', [CredentialsAuthController::class, 'unavailable'])
    ->middleware('throttle:10,1')
    ->name('auth.request');
Route::post('/auth/phone/verify', [CredentialsAuthController::class, 'unavailable'])
    ->middleware('throttle:10,1')
    ->name('auth.verify');
Route::post('/logout', [CredentialsAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/account', AccountController::class)
    ->middleware(['auth', NoIndexPrivateArea::class])
    ->name('account.status');
Route::get('/account/profile', [AccountController::class, 'profile'])
    ->middleware(['auth', NoIndexPrivateArea::class])
    ->name('account.profile');
Route::patch('/account/profile', [AccountController::class, 'updateProfile'])
    ->middleware(['auth', NoIndexPrivateArea::class])
    ->name('account.profile.update');
Route::patch('/account/password', [AccountController::class, 'updatePassword'])
    ->middleware(['auth', NoIndexPrivateArea::class])
    ->name('account.password.update');
Route::patch('/account/preferences', [AccountController::class, 'updatePreferences'])
    ->middleware(['auth', NoIndexPrivateArea::class])
    ->name('account.preferences.update');

Route::prefix('admin')->name('admin.')->middleware(['auth', NoIndexPrivateArea::class])->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/club', [AdminClubController::class, 'index'])->name('club.index');
        Route::post('/club/events', [AdminClubController::class, 'storeEvent'])->name('club.events.store');
        Route::patch('/club/events/{event}', [AdminClubController::class, 'updateEvent'])->name('club.events.update');
        Route::delete('/club/events/{event}', [AdminClubController::class, 'destroyEvent'])->name('club.events.destroy');
        Route::get('/club/polls', [AdminClubController::class, 'polls'])->name('club.polls.index');
        Route::post('/club/polls', [AdminClubController::class, 'storePoll'])->name('club.polls.store');
        Route::patch('/club/polls/{poll}', [AdminClubController::class, 'updatePoll'])->name('club.polls.update');
        Route::delete('/club/polls/{poll}', [AdminClubController::class, 'destroyPoll'])->name('club.polls.destroy');
        Route::post('/club/topics/{topic}/reply', [AdminClubController::class, 'replyTopic'])->name('club.topics.reply');
        Route::patch('/club/topics/{topic}', [AdminClubController::class, 'updateTopic'])->name('club.topics.update');

        Route::get('/content', [AdminContentController::class, 'index'])->name('content.index');
        Route::put('/content/texts', [AdminContentController::class, 'updateTexts'])->name('content.texts.update');
        Route::post('/content/items/{type}', [AdminContentController::class, 'storeItem'])->name('content.items.store');
        Route::patch('/content/items/{item}', [AdminContentController::class, 'updateItem'])->name('content.items.update');
        Route::delete('/content/items/{item}', [AdminContentController::class, 'destroyItem'])->name('content.items.destroy');
        Route::post('/content/blog', [AdminContentController::class, 'storeBlog'])->name('content.blog.store');
        Route::patch('/content/blog/{post}', [AdminContentController::class, 'updateBlog'])->name('content.blog.update');
        Route::delete('/content/blog/{post}', [AdminContentController::class, 'destroyBlog'])->name('content.blog.destroy');

        Route::get('/privacy', [AdminPrivacyController::class, 'index'])->name('privacy.index');
        Route::patch('/privacy/requests/{dataRequest}', [AdminPrivacyController::class, 'update'])->name('privacy.requests.update');
        Route::get('/users', AdminUserRegistryController::class)->name('users.index');
        Route::patch('/users/{user}', [AdminUserRegistryController::class, 'update'])->name('users.access-payment.update');
        Route::patch('/users/{user}/credentials', [AdminUserRegistryController::class, 'resetCredentials'])->name('users.credentials.reset');
        Route::post('/users/{user}/children', [AdminUserRegistryController::class, 'storeChild'])->name('users.children.store');

        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', [AdminSupportController::class, 'index'])->name('index');
            Route::post('/knowledge', [AdminSupportController::class, 'storeKnowledge'])->name('knowledge.store');
            Route::patch('/knowledge/{article}', [AdminSupportController::class, 'updateKnowledge'])->name('knowledge.update');
            Route::delete('/knowledge/{article}', [AdminSupportController::class, 'destroyKnowledge'])->name('knowledge.destroy');
            Route::post('/messages/{message}/knowledge', [AdminSupportController::class, 'promoteMessage'])->name('messages.knowledge');
            Route::get('/{conversation}', [AdminSupportController::class, 'show'])->name('show');
            Route::post('/{conversation}/messages', [AdminSupportController::class, 'storeMessage'])->name('messages.store');
            Route::patch('/{conversation}', [AdminSupportController::class, 'update'])->name('update');
            Route::post('/{conversation}/draft', [AdminSupportController::class, 'draft'])->name('draft');
        });

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

Route::prefix('parent')->name('parent.')->middleware(['auth', 'parent.club', NoIndexPrivateArea::class])->group(function () {
    Route::get('/', ParentDashboardController::class)->name('dashboard');
    Route::get('/forum/data', [ParentForumController::class, 'index'])->name('forum.index');
    Route::post('/forum/topics', [ParentForumController::class, 'storeTopic'])
        ->middleware('throttle:10,1')
        ->name('forum.topics.store');
    Route::post('/forum/topics/{topic}/comments', [ParentForumController::class, 'storeComment'])
        ->middleware('throttle:30,1')
        ->name('forum.comments.store');
    Route::post('/polls/{poll}/vote', [ParentForumController::class, 'votePoll'])
        ->middleware('throttle:30,1')
        ->name('polls.vote');

    Route::post('/events/{event}/response', [ParentClubController::class, 'respondToEvent'])
        ->middleware('throttle:30,1')
        ->name('events.response');
    Route::patch('/notifications/read-all', [ParentClubController::class, 'markAllNotificationsRead'])
        ->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [ParentClubController::class, 'markNotificationRead'])
        ->name('notifications.read');
    Route::patch('/preferences', [ParentClubController::class, 'updatePreferences'])
        ->name('preferences.update');
});
