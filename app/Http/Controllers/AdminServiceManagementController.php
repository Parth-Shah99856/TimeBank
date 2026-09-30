<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin Service Management Controller.
 *
 * Handles listing, viewing, and deleting services from the admin panel.
 */
class AdminServiceManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Service::with(['user', 'category'])->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                  ->orWhere('description', 'like', '%'.$search.'%')
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category_id', $category);
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $services = $query->paginate(20)->withQueryString();

        $categories = \App\Models\Category::orderBy('name')->get();

        return view('admin.services.index', compact('services', 'categories'));
    }

    public function show(Service $service): View
    {
        $service->load(['user', 'category', 'serviceRequests.requester', 'serviceRequests.provider']);
        return view('admin.services.show', compact('service'));
    }

    public function confirmDelete(Service $service): View
    {
        $service->load(['user', 'category']);
        $requestCount = ServiceRequest::where('service_id', $service->id)->count();
        return view('admin.services.confirm-delete', compact('service', 'requestCount'));
    }

    public function destroy(Request $request, Service $service, AdminAuditLog $auditLog): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'in:DELETE'],
        ], [
            'confirm.required' => 'You must type DELETE to confirm.',
            'confirm.in'       => 'You must type DELETE exactly.',
        ]);

        $meta = [
            'service_id'     => $service->id,
            'title'          => $service->title,
            'owner_id'       => $service->user_id,
            'owner_name'     => $service->user?->name,
            'requests_count' => ServiceRequest::where('service_id', $service->id)->count(),
        ];

        try {
            DB::transaction(function () use ($request, $service, $meta): void {
                $service->delete(); // cascades to service_requests which cascade further

                AdminAuditLog::record(
                    admin: $request->user(),
                    action: 'service.deleted',
                    targetType: 'service',
                    targetId: $meta['service_id'],
                    targetLabel: $meta['title'],
                    meta: $meta,
                );
            });
        } catch (\Throwable $e) {
            return redirect()->route('admin.services.index')
                ->with('error', 'Service deletion failed: '.$e->getMessage());
        }

        return redirect()->route('admin.services.index')
            ->with('status', 'Service "'.$meta['title'].'" deleted successfully.');
    }
}