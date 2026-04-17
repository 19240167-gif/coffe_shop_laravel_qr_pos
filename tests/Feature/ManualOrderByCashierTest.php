<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualOrderByCashierTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_create_manual_order_for_walk_in_customer(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $espresso = MenuItem::query()->create([
            'name' => 'Espresso',
            'slug' => 'espresso',
            'price' => 18000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $croissant = MenuItem::query()->create([
            'name' => 'Croissant',
            'slug' => 'croissant',
            'price' => 22000,
            'stock' => 8,
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->post('/dashboard/orders/manual', [
            'customer_name' => 'Walk-in Customer',
            'customer_note' => 'Tanpa HP',
            'items' => [
                $espresso->id => [
                    'quantity' => 2,
                    'notes' => 'No sugar',
                ],
                $croissant->id => [
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertRedirect('/dashboard');

        /** @var \App\Models\Order $order */
        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertSame($cashier->id, $order->handled_by);
        $this->assertNull($order->table_seat_id);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'menu_item_id' => $espresso->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'menu_item_id' => $croissant->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('menu_items', [
            'id' => $espresso->id,
            'stock' => 8,
        ]);

        $this->assertDatabaseHas('menu_items', [
            'id' => $croissant->id,
            'stock' => 7,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $cashier->id,
            'action' => 'order.created.manual',
            'subject_id' => $order->id,
        ]);
    }

    public function test_cashier_cannot_create_manual_order_when_stock_is_insufficient(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $menuItem = MenuItem::query()->create([
            'name' => 'Americano',
            'slug' => 'americano',
            'price' => 20000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $response = $this->from('/dashboard')->actingAs($cashier)->post('/dashboard/orders/manual', [
            'items' => [
                $menuItem->id => [
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItem->id,
            'stock' => 1,
        ]);
    }
}
