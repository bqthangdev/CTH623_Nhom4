<?php

namespace Tests\Feature\Shop;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user    = User::factory()->create();
        $this->product = Product::factory()->inStock(20)->create(['price' => 200000]);

        CartItem::factory()->create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);
    }

    public function test_guest_cannot_access_checkout(): void
    {
        $this->get(route('shop.checkout.index'))
            ->assertRedirect(route('login'));
    }

    public function test_checkout_page_loads_with_cart_items(): void
    {
        $this->actingAs($this->user)
            ->get(route('shop.checkout.index'))
            ->assertOk()
            ->assertViewIs('shop.checkout.index')
            ->assertViewHas('cartItems')
            ->assertViewHas('subtotal');
    }

    public function test_empty_cart_redirects_from_checkout(): void
    {
        CartItem::where('user_id', $this->user->id)->delete();

        $this->actingAs($this->user)
            ->get(route('shop.checkout.index'))
            ->assertRedirect(route('shop.cart.index'));
    }

    public function test_user_can_place_order_with_cod(): void
    {
        $this->actingAs($this->user)
            ->post(route('shop.checkout.store'), [
                'recipient_name'   => 'Nguyễn Văn A',
                'phone'            => '0901234567',
                'shipping_address' => '123 Nguyễn Văn A, Q.1, TP.HCM',
                'payment_method'   => 'cod',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'user_id'        => $this->user->id,
            'payment_method' => 'cod',
        ]);

        // Cart should be cleared
        $this->assertDatabaseMissing('cart_items', ['user_id' => $this->user->id]);

        // Stock should be decremented
        $this->assertEquals(18, $this->product->fresh()->stock);
    }

    public function test_order_items_are_created_after_checkout(): void
    {
        $this->actingAs($this->user)
            ->post(route('shop.checkout.store'), [
                'recipient_name'   => 'Nguyễn Văn A',
                'phone'            => '0901234567',
                'shipping_address' => '123 Test',
                'payment_method'   => 'cod',
            ]);

        $order = Order::where('user_id', $this->user->id)->first();

        $this->assertNotNull($order);
        $this->assertDatabaseHas('order_items', [
            'order_id'   => $order->id,
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);
    }

    public function test_checkout_applies_valid_voucher_discount(): void
    {
        $voucher = Voucher::factory()->create([
            'code'      => 'SAVE50K',
            'type'      => 'fixed',
            'value'     => 50000,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('shop.checkout.store'), [
                'recipient_name'   => 'Nguyễn Văn A',
                'phone'            => '0901234567',
                'shipping_address' => '123 Test',
                'payment_method'   => 'cod',
                'voucher_code'     => 'SAVE50K',
            ]);

        $order = Order::where('user_id', $this->user->id)->first();

        $this->assertNotNull($order);
        $this->assertGreaterThan(0, $order->discount);
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        CartItem::where('user_id', $this->user->id)->delete();

        $this->actingAs($this->user)
            ->post(route('shop.checkout.store'), [
                'recipient_name'   => 'Nguyễn Văn A',
                'phone'            => '0901234567',
                'shipping_address' => '123 Test',
                'payment_method'   => 'cod',
            ])
            ->assertSessionHasErrors();
    }

    public function test_checkout_validates_required_fields(): void
    {
        $this->actingAs($this->user)
            ->post(route('shop.checkout.store'), [])
            ->assertSessionHasErrors(['recipient_name', 'phone', 'shipping_address', 'payment_method']);
    }

    public function test_checkout_fails_when_stock_is_insufficient(): void
    {
        $this->product->update(['stock' => 1]);

        $this->actingAs($this->user)
            ->post(route('shop.checkout.store'), [
                'recipient_name'   => 'Nguyễn Văn A',
                'phone'            => '0901234567',
                'shipping_address' => '123 Test',
                'payment_method'   => 'cod',
            ])
            ->assertSessionHasErrors();
    }
}
