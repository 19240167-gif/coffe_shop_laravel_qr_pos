<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_manage_menu_items(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $response = $this->actingAs($cashier)->post('/dashboard/menu-items', [
            'name' => 'Iced Americano',
            'description' => 'Test item',
            'price' => 25000,
            'stock' => 15,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_manage_menu_items(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post('/dashboard/menu-items', [
            'name' => 'Iced Americano',
            'description' => 'Test item',
            'price' => 25000,
            'stock' => 15,
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('menu_items', [
            'name' => 'Iced Americano',
        ]);
    }

    public function test_cashier_can_update_order_status(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $order = Order::query()->create([
            'order_number' => 'ORD-' . Str::upper(Str::random(8)),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => 10000,
            'tax' => 1000,
            'total' => 11000,
            'ordered_at' => now(),
        ]);

        $response = $this->actingAs($cashier)->post("/dashboard/orders/{$order->id}/status", [
            'status' => 'ready',
            'payment_status' => 'paid',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'ready',
            'payment_status' => 'paid',
            'handled_by' => $cashier->id,
        ]);
    }

    public function test_cashier_cannot_adjust_stock(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $menuItem = MenuItem::query()->create([
            'name' => 'Cafe Latte',
            'slug' => 'cafe-latte',
            'price' => 30000,
            'stock' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->post("/dashboard/menu-items/{$menuItem->id}/stock", [
            'type' => 'in',
            'quantity' => 5,
            'note' => 'test',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItem->id,
            'stock' => 20,
        ]);
    }

    public function test_cashier_cannot_access_bulk_qr_print_page(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $response = $this->actingAs($cashier)->get('/dashboard/table-seats/print');

        $response->assertForbidden();
    }

    public function test_admin_can_access_bulk_qr_print_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard/table-seats/print');

        $response->assertOk();
        $response->assertSee('Cetak QR Meja Massal');
    }
}
