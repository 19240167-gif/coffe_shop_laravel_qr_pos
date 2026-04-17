<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\ActivityLog;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\TableSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $activityLogs = ActivityLog::query()
            ->with('user')
            ->latest('occurred_at')
            ->latest('id')
            ->limit(12)
            ->get();

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
            'activityLogs' => $activityLogs,
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

        $menuItem = MenuItem::query()->create([
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        ActivityLog::record(
            'menu.created',
            "Menu {$menuItem->name} ditambahkan.",
            $menuItem,
            [
                'price' => (float) $menuItem->price,
                'stock' => $menuItem->stock,
            ],
            auth()->id()
        );

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

        $tableSeat = TableSeat::query()->create([
            'code' => strtoupper($validated['code']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        ActivityLog::record(
            'table.created',
            "Meja {$tableSeat->code} ditambahkan.",
            $tableSeat,
            [
                'qr_token' => $tableSeat->qr_token,
            ],
            auth()->id()
        );

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

        ActivityLog::record(
            'order.status_updated',
            "Order {$freshOrder->order_number} diperbarui menjadi {$freshOrder->status}.",
            $freshOrder,
            [
                'status' => $freshOrder->status,
                'payment_status' => $freshOrder->payment_status,
            ],
            auth()->id()
        );

        return redirect()
            ->route('dashboard.index')
            ->with('success', "Status {$freshOrder->order_number} berhasil diperbarui.");
    }

    public function storeManualOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'table_seat_id' => [
                'nullable',
                Rule::exists('table_seats', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'items' => ['required', 'array'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:100'],
        ]);

        $selectedItems = collect($validated['items'])
            ->map(function (array $row, string $menuItemId): array {
                return [
                    'menu_item_id' => (int) $menuItemId,
                    'quantity' => (int) ($row['quantity'] ?? 0),
                    'notes' => $row['notes'] ?? null,
                ];
            })
            ->filter(fn (array $row): bool => $row['quantity'] > 0)
            ->values();

        if ($selectedItems->isEmpty()) {
            return back()
                ->withErrors(['items' => 'Pilih minimal satu menu dengan jumlah lebih dari 0 untuk order manual.'])
                ->withInput();
        }

        $order = DB::transaction(function () use ($selectedItems, $validated) {
            $menuItems = MenuItem::query()
                ->whereIn('id', $selectedItems->pluck('menu_item_id'))
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($menuItems->count() !== $selectedItems->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu menu tidak tersedia atau sedang nonaktif.',
                ]);
            }

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'table_seat_id' => $validated['table_seat_id'] ?? null,
                'handled_by' => auth()->id(),
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_note' => $validated['customer_note'] ?? null,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'ordered_at' => now(),
            ]);

            $subtotal = 0;

            foreach ($selectedItems as $itemRow) {
                /** @var \App\Models\MenuItem $menuItem */
                $menuItem = $menuItems->get($itemRow['menu_item_id']);

                if ($menuItem->stock < $itemRow['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$menuItem->name} tidak mencukupi untuk order manual.",
                    ]);
                }

                $lineTotal = $menuItem->price * $itemRow['quantity'];
                $subtotal += $lineTotal;

                $order->items()->create([
                    'menu_item_id' => $menuItem->id,
                    'menu_name' => $menuItem->name,
                    'unit_price' => $menuItem->price,
                    'quantity' => $itemRow['quantity'],
                    'notes' => $itemRow['notes'],
                    'line_total' => $lineTotal,
                ]);

                $menuItem->decrement('stock', $itemRow['quantity']);

                $menuItem->stockMovements()->create([
                    'user_id' => auth()->id(),
                    'type' => 'out',
                    'quantity' => $itemRow['quantity'],
                    'note' => 'Pengurangan stok dari order manual kasir.',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'occurred_at' => now(),
                ]);
            }

            $order->update([
                'subtotal' => $subtotal,
                'tax' => 0,
                'total' => $subtotal,
            ]);

            return $order->load('tableSeat', 'items');
        });

        OrderCreated::dispatch($order);

        ActivityLog::record(
            'order.created.manual',
            "Order manual {$order->order_number} dibuat oleh kasir.",
            $order,
            [
                'table' => $order->tableSeat?->code,
                'items_count' => $order->items->count(),
                'total' => (float) $order->total,
            ],
            auth()->id()
        );

        return redirect()
            ->route('dashboard.index')
            ->with('success', "Order manual {$order->order_number} berhasil dibuat.");
    }

    public function adjustStock(Request $request, MenuItem $menuItem)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:150'],
        ]);

        $previousStock = $menuItem->stock;
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

        ActivityLog::record(
            'menu.stock_adjusted',
            "Stok {$menuItem->name} diperbarui ({$validated['type']}).",
            $menuItem,
            [
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'from' => $previousStock,
                'to' => $newStock,
            ],
            auth()->id()
        );

        return redirect()
            ->route('dashboard.index')
            ->with('success', "Stok {$menuItem->name} berhasil diperbarui.");
    }

    public function orderCard(Order $order): JsonResponse
    {
        $order->loadMissing(['tableSeat', 'items']);

        $searchBlob = strtolower(trim($order->order_number . ' ' . ($order->tableSeat?->code ?? '') . ' ' . ($order->customer_name ?? '')));

        $html = view('dashboard.partials.order-card', [
            'order' => $order,
            'searchBlob' => $searchBlob,
        ])->render();

        return response()->json([
            'id' => $order->id,
            'status' => $order->status,
            'search_blob' => $searchBlob,
            'html' => $html,
        ]);
    }

    public function printTableSeats()
    {
        $tableSeats = TableSeat::query()->orderBy('code')->get();

        ActivityLog::record(
            'table.print_qr',
            'Membuka halaman cetak QR meja massal.',
            null,
            [
                'tables_count' => $tableSeats->count(),
            ],
            auth()->id()
        );

        return view('dashboard.print-table-seats', [
            'tableSeats' => $tableSeats,
        ]);
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

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
