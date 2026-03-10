<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentMethodRequest;
use App\Http\Requests\Admin\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        $paymentMethods = PaymentMethod::orderBy('sort_order')->get();

        return view('admin.payment-methods.index', compact('paymentMethods'));
    }

    public function create(): View
    {
        return view('admin.payment-methods.create');
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('config');
        $data['config'] = $this->buildConfig($request->input('config_keys', []), $request->input('config_values', []));

        PaymentMethod::create($data);

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Tạo phương thức thanh toán thành công!');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment-methods.edit', compact('paymentMethod'));
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $newIsActive = (bool) $request->boolean('is_active');

        // Enforce at least 1 active payment method
        if (! $newIsActive) {
            $otherActive = PaymentMethod::where('id', '!=', $paymentMethod->id)
                ->where('is_active', true)
                ->exists();

            if (! $otherActive) {
                return back()->with('error', 'Phải có ít nhất một phương thức thanh toán được kích hoạt.');
            }
        }

        $data = $request->safe()->except('config', 'is_active', 'is_external');
        $data['is_active']   = $request->boolean('is_active');
        $data['is_external'] = $request->boolean('is_external');
        $data['config']      = $this->buildConfig($request->input('config_keys', []), $request->input('config_values', []));

        $paymentMethod->update($data);

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Cập nhật phương thức thanh toán thành công!');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $activeCount = PaymentMethod::where('is_active', true)->count();

        if ($paymentMethod->is_active && $activeCount <= 1) {
            return back()->with('error', 'Không thể xóa phương thức thanh toán đang là phương thức duy nhất được kích hoạt.');
        }

        $paymentMethod->delete();

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Đã xóa phương thức thanh toán.');
    }

    /** Build config array from parallel key/value input arrays. */
    private function buildConfig(array $keys, array $values): ?array
    {
        $config = [];

        foreach ($keys as $i => $key) {
            $key = trim((string) $key);
            if ($key !== '') {
                $config[$key] = $values[$i] ?? '';
            }
        }

        return empty($config) ? null : $config;
    }
}
