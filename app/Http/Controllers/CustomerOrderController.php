<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Models\ActivityLog;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\TableSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerOrderController extends Controller
{
    public function show(string $token)
    {
        $tableSeat = TableSeat::query()
            ->where('qr_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $menuItems = MenuItem::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('customer.menu', [
            'tableSeat' => $tableSeat,
            'menuItems' => $menuItems,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $tableSeat = TableSeat::query()
            ->where('qr_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_note' => ['nullable', 'string', 'max:500'],
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
                ->withErrors(['items' => 'Pilih minimal satu menu dengan jumlah lebih dari 0.'])
                ->withInput();
        }

        $order = DB::transaction(function () use ($selectedItems, $validated, $tableSeat) {
            $menuItems = MenuItem::query()
                ->whereIn('id', $selectedItems->pluck('menu_item_id'))
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($menuItems->count() !== $selectedItems->count()) {
                abort(422, 'Salah satu menu tidak tersedia.');
            }

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'table_seat_id' => $tableSeat->id,
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
                    abort(422, "Stok {$menuItem->name} tidak mencukupi.");
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
                    'type' => 'out',
                    'quantity' => $itemRow['quantity'],
                    'note' => 'Pengurangan stok otomatis dari pesanan pelanggan.',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'occurred_at' => now(),
                ]);
            }

            $tax = 0;

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ]);

            return $order->load('tableSeat', 'items');
        });

        ActivityLog::record(
            'order.created.customer',
            "Order {$order->order_number} dibuat dari meja {$tableSeat->code}.",
            $order,
            [
                'table' => $tableSeat->code,
                'items_count' => $order->items->count(),
                'total' => (float) $order->total,
            ]
        );

        OrderCreated::dispatch($order);

        return redirect()
            ->route('customer.table', $tableSeat->qr_token)
            ->with('success', "Pesanan {$order->order_number} berhasil dikirim.");
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
