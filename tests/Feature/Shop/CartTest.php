<?php

namespace Tests\Feature\Shop;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_cart_page(): void
    {
        $this->get(route('shop.cart.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_cart(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('shop.cart.index'))
            ->assertOk()
            ->assertViewIs('shop.cart.index');
    }

    public function test_user_can_add_product_to_cart(): void
    {
        /** @var \App\Models\User $user */
        $user    = User::factory()->create();
        $product = Product::factory()->inStock(10)->create();

        $this->actingAs($user)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'quantity'   => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);
    }

    public function test_adding_same_product_increments_quantity(): void
    {
        /** @var \App\Models\User $user */
        $user    = User::factory()->create();
        $product = Product::factory()->inStock(20)->create();

        $this->actingAs($user)->post(route('shop.cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);

        $this->actingAs($user)->post(route('shop.cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 3,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 5,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_cannot_add_out_of_stock_product(): void
    {
        /** @var \App\Models\User $user */
        $user    = User::factory()->create();
        $product = Product::factory()->outOfStock()->create();

        $this->actingAs($user)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'quantity'   => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_user_can_update_cart_item_quantity(): void
    {
        /** @var \App\Models\User $user */
        $user     = User::factory()->create();
        $product  = Product::factory()->inStock(20)->create();
        $cartItem = CartItem::factory()->create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('shop.cart.update', $cartItem), ['quantity' => 5])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'id'       => $cartItem->id,
            'quantity' => 5,
        ]);
    }

    public function test_user_cannot_update_another_users_cart_item(): void
    {
        /** @var \App\Models\User $user */
        $user      = User::factory()->create();
        $cartItem  = CartItem::factory()->create();

        $this->actingAs($user)
            ->patch(route('shop.cart.update', $cartItem), ['quantity' => 5])
            ->assertForbidden();
    }

    public function test_user_can_remove_cart_item(): void
    {
        /** @var \App\Models\User $user */
        $user     = User::factory()->create();
        $product  = Product::factory()->inStock(10)->create();
        $cartItem = CartItem::factory()->create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->delete(route('shop.cart.destroy', $cartItem))
            ->assertRedirect();

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_add_to_cart_validates_required_fields(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('shop.cart.store'), [])
            ->assertSessionHasErrors(['product_id', 'quantity']);
    }
}
