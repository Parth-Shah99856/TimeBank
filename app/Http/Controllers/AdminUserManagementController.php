<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Idea;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AdminUserDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin User Management Controller.
 *
 * Handles listing, viewing, and deleting users from the admin panel.
 * All methods require auth + EnsureUserIsAdmin middleware (enforced in routes).
 */
class AdminUserManagementController extends Controller
{
    // -------------------------------------------------------------------------
    // User Listing
    // -------------------------------------------------------------------------

    public function index(Request $request, \App\Services\AdminUserQueryService $queryService): View|\Illuminate\Http\JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json($queryService->listAll());
        }

        $query = User::query()->orderByDesc('created_at');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    // -------------------------------------------------------------------------
    // User Detail
    // -------------------------------------------------------------------------

    public function show(User $user): View
    {
        $stats = [
            'services'   => Service::where('user_id', $user->id)->with('category')->latest()->get(),
            'requests'   => ServiceRequest::where('requester_id', $user->id)
                               ->orWhere('provider_id', $user->id)
                               ->with(['service', 'requester', 'provider'])
                               ->latest()->limit(10)->get(),
            'ideas'      => Idea::where('user_id', $user->id)->with('category')->latest()->get(),
            'projects'   => Project::where('lead_user_id', $user->id)->latest()->get(),
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    // -------------------------------------------------------------------------
    // Delete Confirmation (GET)
    // -------------------------------------------------------------------------

    public function confirmDelete(User $user, AdminUserDeletionService $deletionService): View
    {
        $meta = $deletionService->collectMeta($user);
        return view('admin.users.confirm-delete', compact('user', 'meta'));
    }

    // -------------------------------------------------------------------------
    // Delete (POST — server-side confirmed)
    // -------------------------------------------------------------------------

    public function destroy(Request $request, User $user, AdminUserDeletionService $deletionService): RedirectResponse
    {
        // Server-side self-deletion guard
        if ($request->user()->id === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own administrator account.');
        }

        // Require explicit confirmation field in the submitted form
        $request->validate([
            'confirm' => ['required', 'in:DELETE'],
        ], [
            'confirm.required' => 'You must type DELETE to confirm this action.',
            'confirm.in'       => 'You must type DELETE exactly to confirm.',
        ]);

        try {
            $deletionService->delete($request->user(), $user);
        } catch (\Throwable $e) {
            return redirect()->route('admin.users.index')
                ->with('error', 'User deletion failed: '.$e->getMessage());
        }

        return redirect()->route('admin.users.index')
            ->with('status', 'User account and associated data deleted successfully.');
    }
}