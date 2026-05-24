<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedStoreCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return redirect()->route('shop.login')->with('error', 'Vui lòng đăng nhập tài khoản cửa hàng.');
        }

        if (!$customer->canSeeAgencyPrice()) {
            abort(403, 'Tài khoản của bạn chưa được duyệt quyền cửa hàng/đại lý.');
        }

        return $next($request);
    }
}
