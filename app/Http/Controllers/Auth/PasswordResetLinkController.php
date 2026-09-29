<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower(trim((string) $request->input('email')));
        $table = config('auth.passwords.users.table', 'password_reset_tokens');
        $throttleSeconds = (int) config('auth.passwords.users.throttle', 60);

        try {
            $status = Password::sendResetLink(['email' => $email]);
        } catch (Throwable $e) {
            Log::warning('Password reset transmission failed due to mail transport error', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            // Clear any newly created token for this email so user is not locked into a 60s cooldown
            DB::table($table)->where('email', $email)->delete();

            return back()->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Temporal transmission failed. Please verify your connection and try again shortly.',
                ]);
        }

        if ($status === Password::RESET_THROTTLED) {
            $record = DB::table($table)->where('email', $email)->first();
            $secondsRemaining = $throttleSeconds;

            if ($record && isset($record->created_at)) {
                $elapsed = now()->timestamp - Carbon::parse($record->created_at)->timestamp;
                $secondsRemaining = max(1, $throttleSeconds - $elapsed);
            }

            return back()->withInput($request->only('email'))
                ->withErrors([
                    'email' => "Temporal cooldown active. Please wait {$secondsRemaining} seconds before requesting a new recovery key.",
                ]);
        }

        // For security and privacy against account enumeration:
        // Both valid and invalid accounts return a uniform safe confirmation message.
        return back()->with(
            'status',
            'If an active node matches this comm link, temporal recovery instructions have been transmitted.'
        );
    }
}
