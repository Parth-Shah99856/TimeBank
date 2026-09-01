<?php

use App\Http\Controllers\AcceptServiceRequestController;
use App\Http\Controllers\AdminAdjustmentController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminServiceRequestController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CancelServiceRequestController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DisputeServiceRequestController;
use App\Http\Controllers\IdeaCollaboratorController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceRequestCompletionController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\ServiceRequestDisputeResolutionController;
use App\Http\Controllers\ServiceRequestMessageController;
use App\Http\Controllers\ServiceRequestOtpController;
use App\Http\Controllers\ServiceRequestReviewController;
use App\Http\Controllers\StartServiceRequestController;
use App\Http\Controllers\StoreServiceRequestController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — TimeBank Temporal Exchange Interface
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Services Marketplace
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

Route::middleware('auth')->group(function () {
    Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::get('/my-services', function () {
        return view('services.my-services');
    })->name('my-services');
});

Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');

// IdeaVault
Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');

Route::middleware('auth')->group(function () {
    Route::get('/ideas/create', [IdeaController::class, 'create'])->name('ideas.create');
});

Route::get('/ideas/{idea}', [IdeaController::class, 'show'])->name('ideas.show');

// Community Leaderboard
Route::get('/leaderboard', function () {
    return view('leaderboard');
})->name('leaderboard');

// Public User Profiles
Route::get('/users/{user}', [UserProfileController::class, 'show'])->name('users.show');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Wallet & Transactions
    Route::get('/wallet', function () {
        return view('wallet');
    })->name('wallet');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // Service Requests Flow & Lifecycle
    Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests.index');
    Route::get('/service-requests/create', [ServiceRequestController::class, 'create'])->name('service-requests.create');

    Route::post('/service-requests', StoreServiceRequestController::class)
        ->name('service-requests.store');
    Route::post('/service-requests/{service_request}/accept', AcceptServiceRequestController::class)
        ->name('service-requests.accept');
    Route::post('/service-requests/{service_request}/cancel', CancelServiceRequestController::class)
        ->name('service-requests.cancel');
    Route::post('/service-requests/{service_request}/start', StartServiceRequestController::class)
        ->name('service-requests.start');
    Route::post('/service-requests/{service_request}/dispute', DisputeServiceRequestController::class)
        ->name('service-requests.dispute');
    Route::post('/service-requests/{service_request}/resolve-dispute', ServiceRequestDisputeResolutionController::class)
        ->name('service-requests.resolve-dispute');
    Route::post('/service-requests/{service_request}/send-otp', [ServiceRequestOtpController::class, 'send'])
        ->name('service-requests.send-otp');
    Route::post('/service-requests/{service_request}/complete', ServiceRequestCompletionController::class)
        ->name('service-requests.complete');
    Route::post('/service-requests/{service_request}/reviews', ServiceRequestReviewController::class)
        ->name('service-requests.reviews.store');
    Route::get('/service-requests/{service_request}/chat', [ServiceRequestMessageController::class, 'index'])
        ->name('service-requests.chat');
    Route::post('/service-requests/{service_request}/messages', [ServiceRequestMessageController::class, 'store'])
        ->name('service-requests.messages.store');

    // Services CRUD
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::patch('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    // Ideas & Collaborators
    Route::post('/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::patch('/ideas/{idea}', [IdeaController::class, 'update'])->name('ideas.update');
    Route::delete('/ideas/{idea}', [IdeaController::class, 'destroy'])->name('ideas.destroy');
    Route::post('/ideas/{idea}/collaborators', [IdeaCollaboratorController::class, 'store'])->name('ideas.collaborators.store');
    Route::patch('/idea-collaborators/{idea_collaborator}', [IdeaCollaboratorController::class, 'update'])->name('idea-collaborators.update');
    Route::post('/ideas/{idea}/project', [ProjectController::class, 'storeFromIdea'])->name('ideas.project.store');

    // Projects
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::patch('/project-members/{project_member}', [ProjectMemberController::class, 'update'])->name('project-members.update');
    Route::delete('/project-members/{project_member}', [ProjectMemberController::class, 'destroy'])->name('project-members.destroy');
    Route::post('/projects/{project}/tasks', [ProjectTaskController::class, 'store'])->name('projects.tasks.store');
    Route::patch('/project-tasks/{project_task}', [ProjectTaskController::class, 'update'])->name('project-tasks.update');
    Route::delete('/project-tasks/{project_task}', [ProjectTaskController::class, 'destroy'])->name('project-tasks.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Admin Platform Control & Adjustments
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.index');
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/service-requests', [AdminServiceRequestController::class, 'index'])->name('admin.service-requests.index');

    Route::get('/admin/categories', function (Request $request) {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.categories.index');
    })->name('admin.categories.index');

    Route::post('/admin/adjustments', [AdminAdjustmentController::class, 'store'])->name('admin.adjustments.store');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});

require __DIR__.'/auth.php';
