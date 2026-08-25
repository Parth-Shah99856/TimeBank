<?php

use App\Http\Controllers\AcceptServiceRequestController;
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
use App\Http\Controllers\ServiceRequestReviewController;
use App\Http\Controllers\StartServiceRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::patch('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    Route::post('/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::patch('/ideas/{idea}', [IdeaController::class, 'update'])->name('ideas.update');
    Route::delete('/ideas/{idea}', [IdeaController::class, 'destroy'])->name('ideas.destroy');
    Route::post('/ideas/{idea}/collaborators', [IdeaCollaboratorController::class, 'store'])->name('ideas.collaborators.store');
    Route::patch('/idea-collaborators/{idea_collaborator}', [IdeaCollaboratorController::class, 'update'])->name('idea-collaborators.update');
    Route::post('/ideas/{idea}/project', [ProjectController::class, 'storeFromIdea'])->name('ideas.project.store');

    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::patch('/project-members/{project_member}', [ProjectMemberController::class, 'update'])->name('project-members.update');
    Route::delete('/project-members/{project_member}', [ProjectMemberController::class, 'destroy'])->name('project-members.destroy');
    Route::post('/projects/{project}/tasks', [ProjectTaskController::class, 'store'])->name('projects.tasks.store');
    Route::patch('/project-tasks/{project_task}', [ProjectTaskController::class, 'update'])->name('project-tasks.update');
    Route::delete('/project-tasks/{project_task}', [ProjectTaskController::class, 'destroy'])->name('project-tasks.destroy');

    Route::post('/service-requests/{service_request}/accept', AcceptServiceRequestController::class)
        ->name('service-requests.accept');
    Route::post('/service-requests/{service_request}/cancel', CancelServiceRequestController::class)
        ->name('service-requests.cancel');
    Route::post('/service-requests/{service_request}/start', StartServiceRequestController::class)
        ->name('service-requests.start');
    Route::post('/service-requests/{service_request}/dispute', DisputeServiceRequestController::class)
        ->name('service-requests.dispute');
    Route::post('/service-requests/{service_request}/complete', ServiceRequestCompletionController::class)
        ->name('service-requests.complete');
    Route::post('/service-requests/{service_request}/reviews', ServiceRequestReviewController::class)
        ->name('service-requests.reviews.store');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

require __DIR__.'/auth.php';
