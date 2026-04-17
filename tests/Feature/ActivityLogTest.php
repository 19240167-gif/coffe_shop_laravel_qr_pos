<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_menu_item_writes_activity_log(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post('/dashboard/menu-items', [
            'name' => 'Magic Mocha',
            'description' => 'Signature menu',
            'price' => 34000,
            'stock' => 12,
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'menu.created',
        ]);
    }

    public function test_updating_order_status_writes_activity_log(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $order = Order::query()->create([
            'order_number' => 'ORD-' . Str::upper(Str::random(8)),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => 22000,
            'tax' => 0,
            'total' => 22000,
            'ordered_at' => now(),
        ]);

        $response = $this->actingAs($cashier)->post("/dashboard/orders/{$order->id}/status", [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $cashier->id,
            'action' => 'order.status_updated',
            'subject_id' => $order->id,
        ]);
    }
}
