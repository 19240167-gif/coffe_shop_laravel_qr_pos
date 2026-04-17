@php
    $statusClass = 'status-' . $order->status;
    $searchBlob = $searchBlob ?? strtolower(trim($order->order_number . ' ' . ($order->tableSeat?->code ?? '') . ' ' . ($order->customer_name ?? '')));
@endphp

<article class="order-card js-order-card" data-order-id="{{ $order->id }}" data-status="{{ $order->status }}" data-search="{{ $searchBlob }}">
    <div style="display: flex; gap: 10px; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <div>
            <strong>{{ $order->order_number }}</strong>
            <div class="muted">Meja: {{ $order->tableSeat?->code ?? '-' }} | {{ optional($order->ordered_at)->format('d M Y H:i') }}</div>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <span class="status-pill {{ $statusClass }} js-order-status-pill">{{ $order->status }}</span>
            <span class="badge js-order-payment-pill">{{ $order->payment_status }}</span>
            <strong class="js-order-total">Rp{{ number_format($order->total, 0, ',', '.') }}</strong>
        </div>
    </div>

    <ul class="list-clean" style="margin-top: 10px;">
        @foreach ($order->items as $item)
            <li>
                <span>{{ $item->menu_name }} x{{ $item->quantity }}</span>
                <span>Rp{{ number_format($item->line_total, 0, ',', '.') }}</span>
            </li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('dashboard.orders.status', $order) }}" style="margin-top: 10px;" class="grid grid-3">
        @csrf
        <select class="select" name="status" required>
            @foreach (['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'] as $status)
                <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                    {{ strtoupper($status) }}
                </option>
            @endforeach
        </select>
        <select class="select" name="payment_status" required>
            @foreach (['unpaid', 'paid'] as $payment)
                <option value="{{ $payment }}" {{ $order->payment_status === $payment ? 'selected' : '' }}>
                    {{ strtoupper($payment) }}
                </option>
            @endforeach
        </select>
        <button class="btn btn-primary" type="submit">Update Status</button>
    </form>
</article>
