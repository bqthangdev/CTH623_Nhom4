<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(): View
    {
        $vouchers = Voucher::orderByDesc('created_at')->paginate(20);

        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function create(): View
    {
        return view('admin.vouchers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:50', 'unique:vouchers,code'],
            'type'      => ['required', 'in:fixed,percent'],
            'value'     => ['required', 'numeric', 'min:0'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'max_uses'  => ['nullable', 'integer', 'min:1'],
            'expires_at'=> ['nullable', 'date', 'after:today'],
            'is_active' => ['boolean'],
        ]);

        Voucher::create($data);

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Tạo voucher thành công!');
    }

    public function edit(Voucher $voucher): View
    {
        return view('admin.vouchers.edit', compact('voucher'));
    }

    public function update(Request $request, Voucher $voucher): RedirectResponse
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:50', 'unique:vouchers,code,' . $voucher->id],
            'type'      => ['required', 'in:fixed,percent'],
            'value'     => ['required', 'numeric', 'min:0'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'max_uses'  => ['nullable', 'integer', 'min:1'],
            'expires_at'=> ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $voucher->update($data);

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Cập nhật voucher thành công!');
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        $voucher->delete();

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Đã xóa voucher.');
    }
}
