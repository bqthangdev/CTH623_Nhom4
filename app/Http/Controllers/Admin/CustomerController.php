<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::where('role', 'customer')
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->withCount('orders')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        abort_if($customer->role !== 'customer', 404);

        $customer->load(['orders' => fn ($q) => $q->latest()->limit(10)]);

        return view('admin.customers.show', compact('customer'));
    }

    public function toggleActive(User $customer): RedirectResponse
    {
        abort_if($customer->role !== 'customer', 404);

        $customer->update(['is_active' => ! $customer->is_active]);

        $status = $customer->is_active ? 'kích hoạt' : 'vô hiệu hóa';

        return back()->with('success', "Đã {$status} tài khoản khách hàng.");
    }

    public function resetPassword(User $customer): RedirectResponse
    {
        abort_if($customer->role !== 'customer', 404);

        $temporaryPassword = Str::random(12);

        $customer->update([
            'password'             => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        return back()->with('temp_password', $temporaryPassword)
            ->with('success', "Đã đặt lại mật khẩu cho khách hàng. Vui lòng cung cấp mật khẩu tạm thời bên dưới cho người dùng.");
    }
}
