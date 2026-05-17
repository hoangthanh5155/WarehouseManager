<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManageUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->canManageUsers()) {
            abort(403, 'Bạn không có quyền quản lý người dùng.');
        }

        return $next($request);
    }
}
