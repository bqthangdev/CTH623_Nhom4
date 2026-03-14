<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShippingCarrierRequest;
use App\Http\Requests\Admin\UpdateShippingCarrierRequest;
use App\Models\ShippingCarrier;
use App\Services\ShippingCarrierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShippingCarrierController extends Controller
{
    public function __construct(
        private readonly ShippingCarrierService $carrierService,
    ) {}

    public function index(): View
    {
        $carriers = $this->carrierService->getAll();

        return view('admin.shipping-carriers.index', compact('carriers'));
    }

    public function create(): View
    {
        return view('admin.shipping-carriers.create');
    }

    public function store(StoreShippingCarrierRequest $request): RedirectResponse
    {
        $this->carrierService->create($request->validated());

        return redirect()->route('admin.shipping-carriers.index')
            ->with('success', 'Thêm đơn vị vận chuyển thành công!');
    }

    public function edit(ShippingCarrier $shippingCarrier): View
    {
        return view('admin.shipping-carriers.edit', ['carrier' => $shippingCarrier]);
    }

    public function update(UpdateShippingCarrierRequest $request, ShippingCarrier $shippingCarrier): RedirectResponse
    {
        $this->carrierService->update($shippingCarrier, $request->validated());

        return redirect()->route('admin.shipping-carriers.index')
            ->with('success', 'Cập nhật đơn vị vận chuyển thành công!');
    }

    public function destroy(ShippingCarrier $shippingCarrier): RedirectResponse
    {
        $this->carrierService->delete($shippingCarrier);

        return redirect()->route('admin.shipping-carriers.index')
            ->with('success', 'Đã xóa đơn vị vận chuyển.');
    }
}
