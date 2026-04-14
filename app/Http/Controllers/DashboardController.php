<?php

namespace App\Http\Controllers;

use App\Events\OrderStatusUpdated;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\TableSeat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $orders = Order::query()
            ->with(['tableSeat', 'items'])
            ->latest('ordered_at')
            ->latest('id')
            ->limit(30)
            ->get();

        $menuItems = MenuItem::query()->orderBy('name')->get();
        $tableSeats = TableSeat::query()->orderBy('code')->get();

        $summary = [
            'today_revenue' => Order::query()
                ->whereDate('ordered_at', today())
                ->where('payment_status', 'paid')
                ->sum('total'),
            'pending_orders' => Order::query()->where('status', 'pending')->count(),
            'active_tables' => TableSeat::query()->where('is_active', true)->count(),
            'low_stock' => MenuItem::query()->where('stock', '<=', 5)->count(),
        ];

        return view('dashboard.index', [
            'orders' => $orders,
            'menuItems' => $menuItems,
            'tableSeats' => $tableSeats,
            'summary' => $summary,
        ]);
    }

    public function storeMenuItem(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        MenuItem::query()->create([
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()
            ->route('dashboard.index')
            ->with('success', 'Menu baru berhasil ditambahkan.');
    }

    public function storeTable(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:table_seats,code'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        TableSeat::query()->create([
            'code' => strtoupper($validated['code']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()
            ->route('dashboard.index')
            ->with('success', 'Meja baru berhasil ditambahkan.');
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'])],
            'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
        ]);

        $order->update([
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'handled_by' => auth()->id(),
        ]);

        $freshOrder = $order->fresh(['tableSeat', 'items']);
        OrderStatusUpdated::dispatch($freshOrder);

        return redirect()
            ->route('dashboard.index')
            ->with('success', "Status {$freshOrder->order_number} berhasil diperbarui.");
    }

    public function adjustStock(Request $request, MenuItem $menuItem)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:150'],
        ]);

        $newStock = $menuItem->stock;

        if ($validated['type'] === 'in') {
            $newStock += $validated['quantity'];
        }

        if ($validated['type'] === 'out') {
            if ($menuItem->stock < $validated['quantity']) {
                return redirect()
                    ->route('dashboard.index')
                    ->withErrors(['quantity' => "Stok {$menuItem->name} tidak cukup untuk pengurangan."]);
            }

            $newStock -= $validated['quantity'];
        }

        if ($validated['type'] === 'adjustment') {
            $newStock = $validated['quantity'];
        }

        $menuItem->update(['stock' => $newStock]);

        $menuItem->stockMovements()->create([
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'note' => $validated['note'] ?? 'Penyesuaian stok via dashboard.',
            'occurred_at' => now(),
        ]);

        return redirect()
            ->route('dashboard.index')
            ->with('success', "Stok {$menuItem->name} berhasil diperbarui.");
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (MenuItem::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
