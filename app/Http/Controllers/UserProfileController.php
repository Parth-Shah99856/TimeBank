<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    public function show(User $user): View
    {
        // Eager load only public-safe data with tight column selection.
        // Wallet balance, email, transactions, and private service requests
        // are deliberately excluded.
        $user->loadCount([
            'reviewsReceived',
            'providedServiceRequests as completed_exchanges_count' => fn ($q) => $q->where('status', 'completed'),
        ])->load([
            'services' => fn ($q) => $q
                ->with('category')
                ->where('is_active', true)
                ->whereHas('category', fn ($cq) => $cq->where('is_active', true))
                ->latest()
                ->limit(6),
            'reviewsReceived' => fn ($q) => $q
                ->with(['reviewer:id,name,avatar_url'])
                ->latest()
                ->limit(5),
            'ideas' => fn ($q) => $q
                ->with('category')
                ->whereIn('status', ['open', 'recruiting'])
                ->latest()
                ->limit(4),
        ]);

        $avgRating = $user->reviewsReceived->avg('rating');

        return view('users.show', compact('user', 'avgRating'));
    }
}
