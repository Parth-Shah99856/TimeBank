<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), Response::HTTP_FORBIDDEN);

        return view('admin.index');
    }
}
