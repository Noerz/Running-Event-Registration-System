<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Services\AdminLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function __construct(private readonly AdminLoginService $loginService) {}

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $ip = (string) $request->ip();

        $throttle = $this->loginService->tooManyAttempts($email, $ip);

        if ($throttle['locked']) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', [
                    'seconds' => $throttle['seconds'],
                ]),
            ]);
        }

        $credentials = [...$request->only('email', 'password'), 'is_admin' => true];
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            $this->loginService->hit($email, $ip);
            $this->loginService->auditFailure($email, $ip);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $this->loginService->clear($email, $ip);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
