<?php

use App\Http\Controllers\AddBookController;
use App\Http\Controllers\Admin\AdminAnnouncementsController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminBackupController;
use App\Http\Controllers\Admin\AdminBooksController;
use App\Http\Controllers\Admin\AdminCategoriesController;
use App\Http\Controllers\Admin\AdminContactMessagesController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLogsController;
use App\Http\Controllers\Admin\AdminMissingDescriptionsController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminReportsController;
use App\Http\Controllers\Admin\AdminReportsManagementController;
use App\Http\Controllers\Admin\AdminRequestsController;
use App\Http\Controllers\Admin\AdminSystemController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\AnnouncementsController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\SettingsApiController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\EditBookController;
use App\Http\Controllers\EditProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BooksController;
use App\Http\Controllers\BorrowRequestPageController;
use App\Http\Controllers\ConfirmReturnController;
use App\Http\Controllers\MyBorrowedController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestsController;
use App\Http\Controllers\ReturnBookController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupportUsController;
use App\Http\Controllers\Admin\AdminSupportUsController;
use App\Http\Controllers\Admin\AdminTransactionsController;
use Illuminate\Support\Facades\Route;

// ========================================
// PWA Routes
// ========================================
Route::get('/manifest.json', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/offline', [PwaController::class, 'offline'])->name('pwa.offline');

// ========================================
// Home & Static Pages
// ========================================
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/faq', [StaticPageController::class, 'faq'])->name('faq');
Route::get('/about', [StaticPageController::class, 'about'])->name('about');
Route::get('/terms', [StaticPageController::class, 'terms'])->name('terms');
Route::get('/privacy', [StaticPageController::class, 'privacy'])->name('privacy');
Route::get('/guidelines', [StaticPageController::class, 'guidelines'])->name('guidelines');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/report', [ReportController::class, 'show'])->name('report');
Route::post('/report', [ReportController::class, 'store'])->name('report.store');

Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);
Route::get('/register/verify', [RegisterController::class, 'verify'])->name('register.verify');
Route::post('/register/verify', [RegisterController::class, 'handleVerify'])->name('register.verify.handle');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/logout', [LoginController::class, 'logout']);

Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.forgot');
Route::post('/forgot-password', [ForgotPasswordController::class, 'handle'])->name('password.forgot.handle');

Route::prefix('admin')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));

    Route::get('/login', [AdminAuthController::class, 'show'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::match(['get', 'post'], '/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::match(['get', 'post'], '/books', [AdminBooksController::class, 'index'])->name('admin.books.index');
    Route::get('/missing-descriptions', [AdminMissingDescriptionsController::class, 'index'])->name('admin.missing-descriptions.index');
    Route::post('/missing-descriptions/update', [AdminMissingDescriptionsController::class, 'update'])->name('admin.missing-descriptions.update');
    Route::post('/missing-descriptions/skip', [AdminMissingDescriptionsController::class, 'skip'])->name('admin.missing-descriptions.skip');
    Route::match(['get', 'post'], '/users', [AdminUsersController::class, 'index'])->name('admin.users.index');
    Route::match(['get', 'post'], '/announcements', [AdminAnnouncementsController::class, 'index'])->name('admin.announcements.index');
    Route::match(['get', 'post'], '/profile', [AdminProfileController::class, 'index'])->name('admin.profile');
    Route::match(['get', 'post'], '/contact-messages', [AdminContactMessagesController::class, 'index'])->name('admin.contact-messages.index');
    Route::match(['get', 'post'], '/categories', [AdminCategoriesController::class, 'index'])->name('admin.categories.index');
    Route::match(['get', 'post'], '/requests', [AdminRequestsController::class, 'index'])->name('admin.requests.index');
    Route::match(['get', 'post'], '/backup', [AdminBackupController::class, 'index'])->name('admin.backup.index');
    Route::get('/backup/restore', [AdminBackupController::class, 'restore'])->name('admin.backup.restore');
    Route::get('/logs', [AdminLogsController::class, 'index'])->name('admin.logs.index');
    Route::get('/logs/clear', [AdminLogsController::class, 'clear'])->name('admin.logs.clear');
    Route::get('/logs/download', [AdminLogsController::class, 'download'])->name('admin.logs.download');
    Route::get('/system-control', [AdminSystemController::class, 'index'])->name('admin.system-control');
    Route::post('/system-control/execute', [AdminSystemController::class, 'executeCommand'])->name('admin.system-control.execute');
    Route::get('/reports', [AdminReportsController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/export', [AdminReportsController::class, 'export'])->name('admin.reports.export');
    Route::match(['get', 'post'], '/reports-management', [AdminReportsManagementController::class, 'index'])->name('admin.reports-management.index');
    Route::match(['get', 'post'], '/support-us', [AdminSupportUsController::class, 'index'])->name('admin.support-us.index');
    Route::match(['get', 'post'], '/transactions', [AdminTransactionsController::class, 'index'])->name('admin.transactions.index');
});

Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::get('/settings/edit-profile', [EditProfileController::class, 'show'])->name('settings.edit-profile');
Route::post('/settings/edit-profile', [EditProfileController::class, 'update'])->name('settings.edit-profile.update');
Route::get('/settings/change-password', [ChangePasswordController::class, 'show'])->name('settings.change-password');
Route::post('/settings/change-password', [ChangePasswordController::class, 'update'])->name('settings.change-password.update');
Route::match(['get', 'post'], '/api/settings', [SettingsApiController::class, 'handle']);

Route::get('/add-book', [AddBookController::class, 'create'])->name('books.create');
Route::post('/add-book', [AddBookController::class, 'store'])->name('books.store');

Route::get('/edit-book', [EditBookController::class, 'edit'])->name('books.edit');
Route::post('/edit-book', [EditBookController::class, 'update'])->name('books.update');

Route::get('/books', [BooksController::class, 'index'])->name('books');
Route::get('/api/books', [BookApiController::class, 'index']);
Route::match(['get', 'post'], '/book', [BookController::class, 'show'])->name('book.show');

Route::match(['get', 'post'], '/borrow-request', [BorrowRequestPageController::class, 'show'])->name('borrow-request');
Route::get('/my-borrowed', [MyBorrowedController::class, 'index'])->name('my-borrowed');
Route::match(['get', 'post'], '/return-book', [ReturnBookController::class, 'show'])->name('return-book');
Route::match(['get', 'post'], '/confirm-return', [ConfirmReturnController::class, 'show'])->name('confirm-return');

Route::match(['get', 'post'], '/requests', [RequestsController::class, 'index'])->name('requests.index');

Route::match(['get', 'post'], '/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
Route::match(['get', 'post'], '/api/notifications', [NotificationApiController::class, 'index']);

Route::get('/announcements', [AnnouncementsController::class, 'index'])->name('announcements.index');

Route::get('/support-us', [SupportUsController::class, 'show'])->name('support-us');
Route::post('/support-us', [SupportUsController::class, 'store'])->name('support-us.store');

Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');
    if (! file_exists($path)) {
        \Illuminate\Support\Facades\Artisan::call('sitemap:generate');
    }
    return response()->file($path, ['Content-Type' => 'text/xml']);
});

