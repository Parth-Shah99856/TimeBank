<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Maintenance Page — read-only safe diagnostics.
 */
class AdminMaintenanceController extends Controller
{
    public function index(): View
    {
        // Potentially orphaned: services whose user no longer exists
        $orphanedServices = Service::whereDoesntHave('user')->count();

        // Service requests where service was deleted (service_id is null)
        $requestsWithNoService = ServiceRequest::whereNull('service_id')->count();

        // Unverified users (registered after rollout boundary, not demo, not email-verified)
        $rolloutBoundary = config('auth.verification_rollout_at');
        $demoEmails = config('auth.demo_emails', []);
        $unverifiedQuery = User::whereNull('email_verified_at');
        if ($rolloutBoundary) {
            $unverifiedQuery->where('created_at', '>=', \Illuminate\Support\Carbon::parse($rolloutBoundary));
        }
        if (! empty($demoEmails)) {
            $unverifiedQuery->whereNotIn('email', $demoEmails);
        }
        $unverifiedUsers = $unverifiedQuery->orderByDesc('created_at')->limit(20)->get();

        // Recent audit log
        $auditLogs = AdminAuditLog::with('admin')->orderByDesc('created_at')->limit(20)->get();

        // App health metrics
        $health = [
            'laravel_version' => app()->version(),
            'php_version'     => PHP_VERSION,
            'env'             => config('app.env'),
            'debug'           => config('app.debug'),
            'db_connection'   => config('database.default'),
        ];

        return view('admin.maintenance', compact(
            'orphanedServices',
            'requestsWithNoService',
            'unverifiedUsers',
            'auditLogs',
            'health',
        ));
    }
}