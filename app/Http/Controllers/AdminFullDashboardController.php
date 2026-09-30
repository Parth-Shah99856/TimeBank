<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Idea;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Full admin dashboard with rich statistics.
 * Replaces the sparse stub in the original AdminDashboardController.
 */
class AdminFullDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $rolloutBoundary = config('auth.verification_rollout_at');
        $demoEmails = config('auth.demo_emails', []);

        $unverifiedQuery = User::whereNull('email_verified_at');
        if ($rolloutBoundary) {
            $unverifiedQuery->where('created_at', '>=', \Illuminate\Support\Carbon::parse($rolloutBoundary));
        }
        if (! empty($demoEmails)) {
            $unverifiedQuery->whereNotIn('email', $demoEmails);
        }

        $totalUsers = User::count();
        $unverifiedUsersCount = $unverifiedQuery->count();
        $verifiedUsersCount = max(0, $totalUsers - $unverifiedUsersCount);

        $stats = [
            'total_users'       => $totalUsers,
            'admin_users'       => User::where('role', 'admin')->count(),
            'verified_users'    => $verifiedUsersCount,
            'unverified_users'  => $unverifiedUsersCount,
            'total_services'    => Service::count(),
            'active_services'   => Service::where('is_active', true)->count(),
            'total_requests'    => ServiceRequest::count(),
            'pending_requests'  => ServiceRequest::where('status', 'pending')->count(),
            'disputed_requests' => ServiceRequest::where('status', 'disputed')->count(),
            'completed_requests'=> ServiceRequest::where('status', 'completed')->count(),
            'total_ideas'       => Idea::count(),
            'total_projects'    => Project::count(),
            'total_transactions'=> Transaction::count(),
        ];

        $recentUsers = User::orderByDesc('created_at')->limit(5)->get();

        $disputedRequests = ServiceRequest::with(['service', 'requester', 'provider'])
            ->where('status', 'disputed')
            ->latest()
            ->get();

        $recentAuditLogs = AdminAuditLog::with('admin')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $categoryStats = \App\Models\Category::withCount('services')
            ->where('is_active', true)
            ->orderByDesc('services_count')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentUsers',
            'disputedRequests',
            'recentAuditLogs',
            'categoryStats',
        ));
    }
}