<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function show(): View
    {
        return view('shop.change-password');
    }

    public function store(ChangePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password'             => Hash::make($request->validated('password')),
            'must_change_password' => false,
        ]);

        return redirect()->route('home')
            ->with('success', 'Mật khẩu đã được cập nhật thành công.');
    }
}
