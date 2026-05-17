<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->must_change_password) {
            return $next($request);
        }

        if (
            $request->routeIs('profile.password') ||
            $request->routeIs('profile.password.update') ||
            $request->routeIs('logout')
        ) {
            return $next($request);
        }

        return redirect()->route('profile.password')
            ->with('password_required', 'Bạn cần đổi mật khẩu trước khi tiếp tục sử dụng hệ thống.');
    }
}
