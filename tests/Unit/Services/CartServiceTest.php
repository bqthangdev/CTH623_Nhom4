<?php

namespace Tests\Unit\Services;

use App\Models\CartItem;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartService = new CartService();
    }

    public function test_get_items_returns_user_cart_items(): void
    {
        $user    = User::factory()->create();
        $items   = CartItem::factory()->count(3)->create(['user_id' => $user->id]);
        $other   = CartItem::factory()->create();

        $result = $this->cartService->getItems($user);

        $this->assertCount(3, $result);
    }

    public function test_add_item_creates_new_cart_entry(): void
    {
        $user    = User::factory()->create();
        $item    = CartItem::factory()->make();

        $this->cartService->addItem($user, $item->product_id, 2);

        $this->assertDatabaseHas('cart_items', [
            'user_id'    => $user->id,
            'product_id' => $item->product_id,
            'quantity'   => 2,
        ]);
    }

    public function test_add_item_increments_quantity_if_already_in_cart(): void
    {
        $user    = User::factory()->create();
        $cartItem = CartItem::factory()->quantity(3)->create(['user_id' => $user->id]);

        $this->cartService->addItem($user, $cartItem->product_id, 2);

        $this->assertDatabaseHas('cart_items', [
            'id'       => $cartItem->id,
            'quantity' => 5,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_update_quantity_changes_item_quantity(): void
    {
        $cartItem = CartItem::factory()->create(['quantity' => 1]);

        $this->cartService->updateQuantity(
            $cartItem->user,
            $cartItem->id,
            10
        );

        $this->assertDatabaseHas('cart_items', [
            'id'       => $cartItem->id,
            'quantity' => 10,
        ]);
    }

    public function test_remove_item_deletes_from_cart(): void
    {
        $cartItem = CartItem::factory()->create();

        $this->cartService->removeItem($cartItem->user, $cartItem->id);

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_clear_removes_all_user_cart_items(): void
    {
        $user  = User::factory()->create();
        CartItem::factory()->count(4)->create(['user_id' => $user->id]);

        $this->cartService->clear($user);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_get_count_returns_total_quantity(): void
    {
        $user = User::factory()->create();
        CartItem::factory()->quantity(3)->create(['user_id' => $user->id]);
        CartItem::factory()->quantity(2)->create(['user_id' => $user->id]);

        $count = $this->cartService->getCount($user);

        $this->assertEquals(5, $count);
    }
}
