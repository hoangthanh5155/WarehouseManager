<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            return redirect($user->canViewOperationsDashboard() ? route('dashboard') : route('products.index'));
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');
        $identifier = trim($credentials['login']);
        $loginField = str_contains($identifier, '@') ? 'email' : 'name';

        if (!Auth::attempt([$loginField => $identifier, 'password' => $credentials['password']], $remember)) {
            throw ValidationException::withMessages([
                'login' => 'Tên đăng nhập/email hoặc mật khẩu không đúng.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        if ($user->status !== User::STATUS_ACTIVE) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => 'Tài khoản đã bị khóa. Vui lòng liên hệ Chủ kho.',
            ]);
        }

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_user_agent' => (string) $request->userAgent(),
        ])->save();

        $defaultRoute = $user->canViewOperationsDashboard() ? route('dashboard') : route('products.index');

        return redirect()->intended($defaultRoute);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $resetLink = null;
        $user = User::query()->where('email', $request->email)->first();

        if ($user) {
            $token = Password::createToken($user);
            $resetLink = url('/reset-password/' . $token) . '?email=' . urlencode($user->email);
        }

        $response = back()->with('status', 'Nếu email tồn tại trong hệ thống, yêu cầu đặt lại mật khẩu đã được ghi nhận.');

        if (app()->environment('local') && $resetLink) {
            $response->with('reset_link', $resetLink);
        }

        return $response;
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($validated, function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
                'must_change_password' => false,
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
        }

        return redirect()->route('login')->with('status', 'Đã đặt lại mật khẩu. Vui lòng đăng nhập bằng mật khẩu mới.');
    }
}
