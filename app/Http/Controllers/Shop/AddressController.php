<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreAddressRequest;
use App\Http\Requests\Shop\UpdateAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AddressController extends Controller
{
    private const MAX_ADDRESSES = 5;

    public function index(): View
    {
        $addresses = auth()->user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        return view('shop.addresses.index', compact('addresses'));
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $user      = auth()->user();
        $count     = $user->addresses()->count();

        if ($count >= self::MAX_ADDRESSES) {
            return back()->with('error', 'Bạn chỉ được lưu tối đa ' . self::MAX_ADDRESSES . ' địa chỉ.');
        }

        $isDefault = $request->boolean('is_default') || $count === 0;

        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create([
            'recipient_name' => $request->validated()['recipient_name'],
            'phone'          => $request->validated()['phone'],
            'address'        => $request->validated()['address'],
            'is_default'     => $isDefault,
        ]);

        return back()->with('success', 'Địa chỉ đã được lưu.');
    }

    public function edit(UserAddress $address): View
    {
        abort_if($address->user_id !== auth()->id(), 403);

        return view('shop.addresses.edit', compact('address'));
    }

    public function update(UpdateAddressRequest $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== auth()->id(), 403);

        $address->update($request->validated());

        return redirect()->route('shop.addresses.index')->with('success', 'Địa chỉ đã được cập nhật.');
    }

    public function destroy(UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== auth()->id(), 403);

        $wasDefault = $address->is_default;
        $address->delete();

        // Promote the most recent remaining address to default
        if ($wasDefault) {
            $next = auth()->user()->addresses()->orderByDesc('id')->first();
            $next?->update(['is_default' => true]);
        }

        return back()->with('success', 'Địa chỉ đã được xóa.');
    }

    public function setDefault(UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== auth()->id(), 403);

        auth()->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return back()->with('success', 'Đã đặt làm địa chỉ mặc định.');
    }
}
