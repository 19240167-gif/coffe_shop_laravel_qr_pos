@extends('layouts.app')

@section('title', 'Dashboard Kasir')

@section('content')
    @php
        $isAdmin = auth()->user()?->role === 'admin';
    @endphp

    <section class="page-hero">
        <div>
            <h1>Dashboard Admin/Kasir</h1>
            <p>Pusat kontrol menu, stok, pesanan, dan monitoring operasional restoran.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <span class="badge">Pusher-ready</span>
            <span class="badge">Realtime Orders</span>
        </div>
    </section>

    <section class="grid grid-4" style="margin-bottom: 18px;">
        <article class="card">
            <span class="muted">Pendapatan Hari Ini</span>
            <p class="summary-value">Rp{{ number_format($summary['today_revenue'], 0, ',', '.') }}</p>
        </article>
        <article class="card">
            <span class="muted">Order Pending</span>
            <p class="summary-value">{{ $summary['pending_orders'] }}</p>
        </article>
        <article class="card">
            <span class="muted">Meja Aktif</span>
            <p class="summary-value">{{ $summary['active_tables'] }}</p>
        </article>
        <article class="card">
            <span class="muted">Menu Low Stock</span>
            <p class="summary-value">{{ $summary['low_stock'] }}</p>
        </article>
    </section>

    <section class="grid grid-2" style="margin-bottom: 18px;">
        <article class="card">
            @if ($isAdmin)
                <h2 class="section-title">Tambah Menu Baru</h2>
                <form method="POST" action="{{ route('dashboard.menu-items.store') }}">
                    @csrf
                    <div class="field">
                        <label for="menu-name">Nama Menu</label>
                        <input id="menu-name" class="input" type="text" name="name" required>
                    </div>
                    <div class="field">
                        <label for="menu-description">Deskripsi</label>
                        <textarea id="menu-description" class="textarea" name="description" placeholder="Contoh: Kopi arabika, notes cokelat"></textarea>
                    </div>
                    <div class="grid grid-2">
                        <div class="field">
                            <label for="menu-price">Harga</label>
                            <input id="menu-price" class="input" type="number" name="price" min="0" step="100" required>
                        </div>
                        <div class="field">
                            <label for="menu-stock">Stok Awal</label>
                            <input id="menu-stock" class="input" type="number" name="stock" min="0" required>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Simpan Menu</button>
                </form>

                <hr style="border: 0; border-top: 1px solid var(--line); margin: 20px 0;">

                <h2 class="section-title">Tambah Meja QR</h2>
                <form method="POST" action="{{ route('dashboard.table-seats.store') }}">
                    @csrf
                    <div class="field">
                        <label for="table-code">Kode Meja</label>
                        <input id="table-code" class="input" type="text" name="code" placeholder="Contoh: A1" required>
                    </div>
                    <button class="btn btn-soft" type="submit">Buat Meja</button>
                </form>
            @else
                <h2 class="section-title">Akses Kasir</h2>
                <p class="muted">Akun kasir fokus pada pemrosesan order. Pengelolaan menu, stok, dan meja hanya untuk admin.</p>
            @endif

            <p class="footer-note">
                Link pelanggan: <span class="muted">/table/{qr_token}</span>
            </p>
        </article>

        <article class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                <h2 class="section-title" style="margin: 0;">Menu dan Penyesuaian Stok</h2>
                <input id="menu-search" class="input" type="text" placeholder="Cari menu..." style="max-width: 220px;">
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Menu</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Aksi Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($menuItems as $menuItem)
                            @php
                                $menuSearchKey = strtolower($menuItem->name . ' ' . $menuItem->slug);
                            @endphp
                            <tr class="js-menu-row" data-menu-key="{{ $menuSearchKey }}">
                                <td>
                                    <strong>{{ $menuItem->name }}</strong>
                                    <div class="muted">{{ $menuItem->slug }}</div>
                                </td>
                                <td>Rp{{ number_format($menuItem->price, 0, ',', '.') }}</td>
                                <td>{{ $menuItem->stock }}</td>
                                <td>
                                    @if ($isAdmin)
                                        <form method="POST" action="{{ route('dashboard.menu-items.stock', $menuItem) }}" style="display: grid; gap: 8px;">
                                            @csrf
                                            <select class="select" name="type" required>
                                                <option value="in">Tambah (+)</option>
                                                <option value="out">Kurangi (-)</option>
                                                <option value="adjustment">Set langsung</option>
                                            </select>
                                            <input class="input" type="number" name="quantity" min="1" placeholder="Jumlah" required>
                                            <input class="input" type="text" name="note" placeholder="Catatan opsional">
                                            <button class="btn btn-soft" type="submit">Simpan</button>
                                        </form>
                                    @else
                                        <span class="muted">Hanya admin</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="muted">Belum ada menu. Tambahkan menu dari panel kiri.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="card" id="orders-section">
        <h2 class="section-title">Pesanan Masuk</h2>
        <p class="muted" style="margin-top: -4px;">Daftar ini bisa difilter berdasarkan status dan nomor order.</p>

        <div class="order-toolbar">
            <div class="chip-group">
                <button class="chip-btn order-filter-btn active" data-filter="all" type="button">Semua</button>
                <button class="chip-btn order-filter-btn" data-filter="pending" type="button">Pending</button>
                <button class="chip-btn order-filter-btn" data-filter="preparing" type="button">Preparing</button>
                <button class="chip-btn order-filter-btn" data-filter="ready" type="button">Ready</button>
                <button class="chip-btn order-filter-btn" data-filter="completed" type="button">Completed</button>
            </div>
            <input id="order-search" class="input" type="text" placeholder="Cari nomor order atau meja..." style="max-width: 250px;">
        </div>

        <div style="margin-top: 12px;" id="orders-list">
            @forelse ($orders as $order)
                @php
                    $statusClass = 'status-' . $order->status;
                    $searchBlob = strtolower(trim($order->order_number . ' ' . ($order->tableSeat?->code ?? '') . ' ' . ($order->customer_name ?? '')));
                @endphp
                <article class="order-card js-order-card" data-status="{{ $order->status }}" data-search="{{ $searchBlob }}">
                    <div style="display: flex; gap: 10px; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                        <div>
                            <strong>{{ $order->order_number }}</strong>
                            <div class="muted">Meja: {{ $order->tableSeat?->code ?? '-' }} | {{ optional($order->ordered_at)->format('d M Y H:i') }}</div>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <span class="status-pill {{ $statusClass }}">{{ $order->status }}</span>
                            <span class="badge">{{ $order->payment_status }}</span>
                            <strong>Rp{{ number_format($order->total, 0, ',', '.') }}</strong>
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
            @empty
                <p class="muted">Belum ada pesanan yang masuk.</p>
            @endforelse
            <p id="orders-empty-state" class="muted" style="display: none;">Tidak ada pesanan yang cocok dengan filter.</p>
        </div>
    </section>

    <section class="card" style="margin-top: 18px;">
        <h2 class="section-title">Daftar Meja</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>QR Token</th>
                        <th>QR</th>
                        <th>URL Scan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tableSeats as $tableSeat)
                        <tr>
                            <td>{{ $tableSeat->code }}</td>
                            <td>{{ $tableSeat->qr_token }}</td>
                            <td>
                                <div class="qr-box">
                                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(90)->margin(1)->generate(route('customer.table', $tableSeat->qr_token)) !!}
                                </div>
                            </td>
                            <td>
                                <div class="table-link-actions">
                                    <a class="link-inline" href="{{ route('customer.table', $tableSeat->qr_token) }}" target="_blank" rel="noopener noreferrer">
                                        {{ route('customer.table', $tableSeat->qr_token) }}
                                    </a>
                                    <button type="button" class="btn btn-soft btn-xs copy-url-btn" data-url="{{ route('customer.table', $tableSeat->qr_token) }}">Copy</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">Belum ada meja terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toastStack = document.getElementById('toast-stack');
            const createToast = (message, type) => {
                if (!toastStack) {
                    return;
                }

                const toast = document.createElement('div');
                toast.className = 'toast-item toast-' + (type || 'info');
                toast.textContent = message;
                toastStack.appendChild(toast);

                window.setTimeout(function () {
                    toast.classList.add('toast-hide');
                    window.setTimeout(function () {
                        toast.remove();
                    }, 260);
                }, 3200);
            };

            const menuSearchInput = document.getElementById('menu-search');
            const menuRows = Array.from(document.querySelectorAll('.js-menu-row'));

            if (menuSearchInput) {
                menuSearchInput.addEventListener('input', function () {
                    const keyword = menuSearchInput.value.toLowerCase().trim();

                    menuRows.forEach(function (row) {
                        const matched = row.dataset.menuKey.includes(keyword);
                        row.style.display = matched ? '' : 'none';
                    });
                });
            }

            const orderCards = Array.from(document.querySelectorAll('.js-order-card'));
            const orderFilterButtons = Array.from(document.querySelectorAll('.order-filter-btn'));
            const orderSearchInput = document.getElementById('order-search');
            const emptyOrderState = document.getElementById('orders-empty-state');
            let activeFilter = 'all';

            const applyOrderFilter = () => {
                const keyword = (orderSearchInput ? orderSearchInput.value : '').toLowerCase().trim();
                let visibleCount = 0;

                orderCards.forEach(function (card) {
                    const matchedStatus = activeFilter === 'all' || card.dataset.status === activeFilter;
                    const matchedSearch = keyword === '' || card.dataset.search.includes(keyword);
                    const shouldShow = matchedStatus && matchedSearch;

                    card.style.display = shouldShow ? '' : 'none';

                    if (shouldShow) {
                        visibleCount += 1;
                    }
                });

                if (emptyOrderState) {
                    emptyOrderState.style.display = visibleCount === 0 && orderCards.length > 0 ? '' : 'none';
                }
            };

            orderFilterButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    activeFilter = button.dataset.filter;

                    orderFilterButtons.forEach(function (btn) {
                        btn.classList.remove('active');
                    });

                    button.classList.add('active');
                    applyOrderFilter();
                });
            });

            if (orderSearchInput) {
                orderSearchInput.addEventListener('input', applyOrderFilter);
            }

            applyOrderFilter();

            document.querySelectorAll('.copy-url-btn').forEach(function (button) {
                button.addEventListener('click', async function () {
                    const url = button.dataset.url;

                    try {
                        await navigator.clipboard.writeText(url);
                        createToast('Link meja berhasil disalin.', 'success');
                    } catch (error) {
                        createToast('Gagal menyalin link meja.', 'error');
                    }
                });
            });

            if (!window.Echo) {
                return;
            }

            window.Echo.channel('orders')
                .listen('.order.created', function (payload) {
                    createToast('Order baru masuk: ' + (payload.order_number || 'Baru'), 'success');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 1400);
                })
                .listen('.order.status-updated', function (payload) {
                    createToast('Status diperbarui: ' + (payload.order_number || 'Order'), 'info');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 1200);
                });
        });
    </script>
@endpush
