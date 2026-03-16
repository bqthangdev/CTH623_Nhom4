<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user()?->must_change_password
            && ! $request->routeIs('password.change', 'password.change.store', 'logout')
        ) {
            return redirect()->route('password.change')
                ->with('warning', 'Vui lòng đặt mật khẩu mới trước khi tiếp tục.');
        }

        return $next($request);
    }
}
