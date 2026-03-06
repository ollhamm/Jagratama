<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\Web\ApprovalPageController;
use App\Http\Controllers\Web\DocumentPageController;
use App\Http\Controllers\Web\UserManagementPageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::redirect('/login', '/signin')->name('login');

Route::middleware('guest')->group(function () {
    Route::view('/signin', 'pages.auth.signin', ['title' => 'Sign In'])->name('signin');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Blade UI routes
    Route::get('/app/documents', [DocumentPageController::class, 'index'])->name('app.documents.index');
    Route::get('/app/documents/create', [DocumentPageController::class, 'create'])->name('app.documents.create');
    Route::post('/app/documents', [DocumentPageController::class, 'store'])->name('app.documents.store');
    Route::get('/app/documents/{id}', [DocumentPageController::class, 'show'])->name('app.documents.show');
    Route::post('/app/documents/{id}/submit', [DocumentPageController::class, 'submit'])->name('app.documents.submit');
    Route::get('/app/documents/{id}/download', [DocumentPageController::class, 'download'])->name('app.documents.download');

    Route::get('/app/approvals/pending', [ApprovalPageController::class, 'pending'])->name('app.approvals.pending');
    Route::post('/app/approvals/{id}/approve', [ApprovalPageController::class, 'approve'])->name('app.approvals.approve');
    Route::post('/app/approvals/{id}/reject', [ApprovalPageController::class, 'reject'])->name('app.approvals.reject');

    Route::middleware('role:ADMIN')->group(function () {
        Route::get('/app/users', [UserManagementPageController::class, 'index'])->name('app.users.index');
        Route::get('/app/users/create', [UserManagementPageController::class, 'create'])->name('app.users.create');
        Route::post('/app/users', [UserManagementPageController::class, 'store'])->name('app.users.store');
        Route::get('/app/users/{id}/edit', [UserManagementPageController::class, 'edit'])->name('app.users.edit');
        Route::put('/app/users/{id}', [UserManagementPageController::class, 'update'])->name('app.users.update');
        Route::delete('/app/users/{id}', [UserManagementPageController::class, 'destroy'])->name('app.users.destroy');
    });

    // REST API endpoints (session-based + CSRF protected)
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{id}', [DocumentController::class, 'show']);
    Route::post('/documents/{id}/submit', [DocumentController::class, 'submit']);

    Route::get('/approvals/pending', [ApprovalController::class, 'pending']);
    Route::post('/approvals/{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('/approvals/{id}/reject', [ApprovalController::class, 'reject']);

    Route::post('/signatures', [SignatureController::class, 'store']);
});

Route::view('/error-404', 'pages.errors.error-404', ['title' => 'Error 404'])->name('error-404');
