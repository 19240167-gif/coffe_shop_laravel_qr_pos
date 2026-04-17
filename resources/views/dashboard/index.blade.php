@extends('layouts.app')

@section('title', 'Dashboard Kasir')

@section('content')
    @php
        $isAdmin = auth()->user()?->role === 'admin';
        $roleLabel = strtoupper((string) auth()->user()?->role);
    @endphp

    <section class="page-hero">
        <div>
            <h1>Dashboard Admin/Kasir</h1>
            <p>Pusat kontrol menu, stok, pesanan, dan monitoring operasional restoran.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <span class="badge">Role: {{ $roleLabel }}</span>
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
            <div class="section-bar">
                <h2 class="section-title" style="margin: 0;">Aktivitas Sistem</h2>
                <span class="badge">Audit Log</span>
            </div>

            <div class="activity-feed">
                @forelse ($activityLogs as $activityLog)
                    <article class="activity-item">
                        <div class="activity-head">
                            <span class="badge activity-action">{{ $activityLog->action }}</span>
                            <small class="muted">{{ optional($activityLog->occurred_at)->format('d M Y H:i') }}</small>
                        </div>
                        <p>{{ $activityLog->description }}</p>
                        <small class="muted">User: {{ $activityLog->user?->name ?? 'System' }}</small>
                    </article>
                @empty
                    <p class="muted">Belum ada aktivitas tercatat.</p>
                @endforelse
            </div>
        </article>

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

    <section class="card" style="margin-bottom: 18px;" id="manual-order-section">
        <div class="section-bar">
            <div>
                <h2 class="section-title" style="margin: 0;">Order Manual Kasir</h2>
                <p class="muted" style="margin: 4px 0 0;">Gunakan untuk pelanggan yang tidak membawa HP atau tidak bisa scan QR.</p>
            </div>
            <span class="badge">Walk-in Ready</span>
        </div>

        <form method="POST" action="{{ route('dashboard.orders.manual.store') }}">
            @csrf

            <div class="grid grid-2 manual-order-grid">
                <article class="manual-order-side">
                    <div class="field">
                        <label for="manual-customer-name">Nama Pelanggan (opsional)</label>
                        <input
                            id="manual-customer-name"
                            class="input"
                            type="text"
                            name="customer_name"
                            value="{{ old('customer_name') }}"
                            placeholder="Contoh: Walk-in Bapak Arif"
                        >
                    </div>

                    <div class="field">
                        <label for="manual-table-seat">Pilih Meja (opsional)</label>
                        <select id="manual-table-seat" class="select" name="table_seat_id">
                            <option value="">Walk-in (tanpa meja)</option>
                            @foreach ($tableSeats->where('is_active', true) as $tableSeat)
                                <option value="{{ $tableSeat->id }}" {{ (string) old('table_seat_id') === (string) $tableSeat->id ? 'selected' : '' }}>
                                    Meja {{ $tableSeat->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="manual-customer-note">Catatan Umum</label>
                        <textarea
                            id="manual-customer-note"
                            class="textarea"
                            name="customer_note"
                            placeholder="Contoh: Pelanggan alergi kacang"
                        >{{ old('customer_note') }}</textarea>
                    </div>

                    <ul id="manual-order-preview" class="cart-preview list-clean">
                        <li class="muted">Belum ada menu dipilih.</li>
                    </ul>

                    <div class="cart-total-wrap">
                        <div class="cart-total-row">
                            <span class="muted">Total Item</span>
                            <strong id="manual-order-total-items">0</strong>
                        </div>
                        <div class="cart-total-row">
                            <span class="muted">Estimasi Total</span>
                            <strong id="manual-order-total-price">Rp0</strong>
                        </div>
                    </div>

                    <button id="manual-order-submit" class="btn btn-primary btn-block" type="submit" disabled>Buat Order Manual</button>
                </article>

                <article>
                    <div class="manual-menu-grid">
                        @foreach ($menuItems as $menuItem)
                            <article class="manual-menu-card {{ $menuItem->stock <= 0 ? 'manual-menu-card-disabled' : '' }}">
                                <div class="menu-head">
                                    <h3>{{ $menuItem->name }}</h3>
                                    <span class="stock-chip {{ $menuItem->stock <= 0 ? 'stock-empty' : 'stock-ready' }}">
                                        {{ $menuItem->stock <= 0 ? 'Habis' : 'Stok: ' . $menuItem->stock }}
                                    </span>
                                </div>

                                <p class="price-tag">Rp{{ number_format($menuItem->price, 0, ',', '.') }}</p>

                                <div class="qty-control">
                                    <button
                                        class="qty-btn manual-qty-btn"
                                        type="button"
                                        data-target="manual-qty-{{ $menuItem->id }}"
                                        data-step="-1"
                                        {{ $menuItem->stock <= 0 ? 'disabled' : '' }}
                                    >-</button>

                                    <input
                                        id="manual-qty-{{ $menuItem->id }}"
                                        class="input manual-qty-input"
                                        type="number"
                                        min="0"
                                        max="{{ $menuItem->stock }}"
                                        name="items[{{ $menuItem->id }}][quantity]"
                                        value="{{ old("items.{$menuItem->id}.quantity", 0) }}"
                                        data-menu-name="{{ $menuItem->name }}"
                                        data-menu-price="{{ $menuItem->price }}"
                                        {{ $menuItem->stock <= 0 ? 'disabled' : '' }}
                                    >

                                    <button
                                        class="qty-btn manual-qty-btn"
                                        type="button"
                                        data-target="manual-qty-{{ $menuItem->id }}"
                                        data-step="1"
                                        {{ $menuItem->stock <= 0 ? 'disabled' : '' }}
                                    >+</button>
                                </div>

                                <div class="field" style="margin-bottom: 0;">
                                    <label for="manual-note-{{ $menuItem->id }}">Catatan Item</label>
                                    <input
                                        id="manual-note-{{ $menuItem->id }}"
                                        class="input"
                                        type="text"
                                        name="items[{{ $menuItem->id }}][notes]"
                                        value="{{ old("items.{$menuItem->id}.notes") }}"
                                        placeholder="Opsional"
                                        {{ $menuItem->stock <= 0 ? 'disabled' : '' }}
                                    >
                                </div>
                            </article>
                        @endforeach
                    </div>
                </article>
            </div>
        </form>
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
            <p id="orders-no-data" class="muted" style="{{ $orders->isEmpty() ? '' : 'display: none;' }}">Belum ada pesanan yang masuk.</p>

            @foreach ($orders as $order)
                @php
                    $searchBlob = strtolower(trim($order->order_number . ' ' . ($order->tableSeat?->code ?? '') . ' ' . ($order->customer_name ?? '')));
                @endphp
                @include('dashboard.partials.order-card', ['order' => $order, 'searchBlob' => $searchBlob])
            @endforeach

            <p id="orders-empty-state" class="muted" style="display: none;">Tidak ada pesanan yang cocok dengan filter.</p>
        </div>
    </section>

    <section class="card" style="margin-top: 18px;">
        <div class="section-bar">
            <h2 class="section-title" style="margin: 0;">Daftar Meja</h2>
            @if ($isAdmin)
                <a class="btn btn-soft btn-xs" href="{{ route('dashboard.table-seats.print') }}" target="_blank" rel="noopener noreferrer">
                    Print QR Massal
                </a>
            @endif
        </div>

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
            const orderCardRouteTemplate = @json(route('dashboard.orders.card', ['order' => '__ORDER_ID__']));
            const toastStack = document.getElementById('toast-stack');
            const ordersList = document.getElementById('orders-list');
            const noDataState = document.getElementById('orders-no-data');
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

            const getOrderCards = () => Array.from(document.querySelectorAll('.js-order-card'));

            const syncNoDataState = () => {
                if (!noDataState) {
                    return;
                }

                noDataState.style.display = getOrderCards().length === 0 ? '' : 'none';
            };

            const menuSearchInput = document.getElementById('menu-search');
            const menuRows = Array.from(document.querySelectorAll('.js-menu-row'));
            const manualQtyInputs = Array.from(document.querySelectorAll('.manual-qty-input'));
            const manualPreview = document.getElementById('manual-order-preview');
            const manualTotalItems = document.getElementById('manual-order-total-items');
            const manualTotalPrice = document.getElementById('manual-order-total-price');
            const manualSubmit = document.getElementById('manual-order-submit');
            const rupiah = new Intl.NumberFormat('id-ID');

            const clampManualInput = (input) => {
                const min = Number(input.min || 0);
                const max = Number(input.max || Number.MAX_SAFE_INTEGER);
                const current = Number(input.value || 0);

                if (Number.isNaN(current)) {
                    input.value = String(min);
                    return;
                }

                input.value = String(Math.min(max, Math.max(min, current)));
            };

            const renderManualSummary = () => {
                if (!manualPreview || !manualTotalItems || !manualTotalPrice || !manualSubmit) {
                    return;
                }

                let totalItems = 0;
                let totalPrice = 0;

                const selectedItems = manualQtyInputs
                    .map((input) => {
                        return {
                            name: input.dataset.menuName,
                            price: Number(input.dataset.menuPrice || 0),
                            quantity: Number(input.value || 0),
                        };
                    })
                    .filter((item) => item.quantity > 0);

                manualPreview.innerHTML = '';

                if (selectedItems.length === 0) {
                    const emptyNode = document.createElement('li');
                    emptyNode.className = 'muted';
                    emptyNode.textContent = 'Belum ada menu dipilih.';
                    manualPreview.appendChild(emptyNode);
                    manualSubmit.disabled = true;
                } else {
                    selectedItems.forEach((item) => {
                        totalItems += item.quantity;
                        totalPrice += item.price * item.quantity;

                        const row = document.createElement('li');
                        row.className = 'cart-item-row';

                        const left = document.createElement('span');
                        left.textContent = item.name + ' x' + item.quantity;

                        const right = document.createElement('strong');
                        right.textContent = 'Rp' + rupiah.format(item.price * item.quantity);

                        row.appendChild(left);
                        row.appendChild(right);
                        manualPreview.appendChild(row);
                    });

                    manualSubmit.disabled = false;
                }

                manualTotalItems.textContent = String(totalItems);
                manualTotalPrice.textContent = 'Rp' + rupiah.format(totalPrice);
            };

            document.querySelectorAll('.manual-qty-btn').forEach((button) => {
                button.addEventListener('click', function () {
                    const input = document.getElementById(button.dataset.target);

                    if (!input || input.disabled) {
                        return;
                    }

                    const step = Number(button.dataset.step || 0);
                    const current = Number(input.value || 0);
                    input.value = String(current + step);
                    clampManualInput(input);
                    renderManualSummary();
                });
            });

            manualQtyInputs.forEach((input) => {
                input.addEventListener('input', function () {
                    clampManualInput(input);
                    renderManualSummary();
                });

                input.addEventListener('change', function () {
                    clampManualInput(input);
                    renderManualSummary();
                });
            });

            if (menuSearchInput) {
                menuSearchInput.addEventListener('input', function () {
                    const keyword = menuSearchInput.value.toLowerCase().trim();

                    menuRows.forEach(function (row) {
                        const matched = row.dataset.menuKey.includes(keyword);
                        row.style.display = matched ? '' : 'none';
                    });
                });
            }

            const orderFilterButtons = Array.from(document.querySelectorAll('.order-filter-btn'));
            const orderSearchInput = document.getElementById('order-search');
            const emptyOrderState = document.getElementById('orders-empty-state');
            let activeFilter = 'all';

            const applyOrderFilter = () => {
                const orderCards = getOrderCards();
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

            const upsertOrderCard = async (orderId, mode = 'prepend') => {
                if (!ordersList || !orderId) {
                    return;
                }

                const endpoint = orderCardRouteTemplate.replace('__ORDER_ID__', String(orderId));
                const response = await fetch(endpoint, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Gagal mengambil snapshot order realtime.');
                }

                const payload = await response.json();
                const holder = document.createElement('div');
                holder.innerHTML = String(payload.html || '').trim();

                const nextCard = holder.firstElementChild;

                if (!nextCard) {
                    return;
                }

                const existing = ordersList.querySelector('[data-order-id="' + String(orderId) + '"]');

                if (existing) {
                    existing.replaceWith(nextCard);
                } else if (mode === 'prepend') {
                    ordersList.prepend(nextCard);
                } else {
                    ordersList.appendChild(nextCard);
                }

                syncNoDataState();
                applyOrderFilter();
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

            renderManualSummary();
            syncNoDataState();
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
                .listen('.order.created', async function (payload) {
                    createToast('Order baru masuk: ' + (payload.order_number || 'Baru'), 'success');

                    try {
                        await upsertOrderCard(payload.id, 'prepend');
                    } catch (error) {
                        createToast('Realtime sinkronisasi order baru gagal.', 'error');
                    }
                })
                .listen('.order.status-updated', async function (payload) {
                    createToast('Status diperbarui: ' + (payload.order_number || 'Order'), 'info');

                    try {
                        await upsertOrderCard(payload.id, 'append');
                    } catch (error) {
                        createToast('Realtime sinkronisasi status order gagal.', 'error');
                    }
                });
        });
    </script>
@endpush
