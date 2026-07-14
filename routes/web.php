<?php

use App\Http\Controllers\AddBookController;
use App\Http\Controllers\AnnouncementsController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaticPageController;
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
use Illuminate\Support\Facades\Route;

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

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/logout', [LoginController::class, 'logout']);

Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.forgot');
Route::post('/forgot-password', [ForgotPasswordController::class, 'handle'])->name('password.forgot.handle');

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

Route::get('/book-cards-demo', function () {
    $books = [
        [
            'id' => 'BK001',
            'title' => 'The Hobbit',
            'author' => 'J.R.R. Tolkien',
            'category' => 'Fantasy',
            'status' => 'available',
            'rating' => 4.7,
            'rating_count' => 18,
            'owner_id' => 'USR001',
            'owner_name' => 'Aisha Rahman',
            'owner_avatar' => 'aisha.jpg',
            'hall' => '1',
            'cover_image' => '',
        ],
        [
            'id' => 'BK002',
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'category' => 'Programming',
            'status' => 'borrowed',
            'rating' => 4.4,
            'rating_count' => 12,
            'owner_id' => 'USR002',
            'owner_name' => 'Nabil Hasan',
            'owner_avatar' => '',
            'hall' => '3',
            'cover_image' => '',
        ],
    ];

    return view('demo.book-cards', compact('books'));
});
