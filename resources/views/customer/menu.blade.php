@extends('layouts.app')

@section('title', 'Pesan Menu - Meja ' . $tableSeat->code)

@section('content')
    <section class="page-hero">
        <div>
            <h1>Pesan Instan Untuk Meja {{ $tableSeat->code }}</h1>
            <p>Semua item diproses langsung ke kasir. Pilih menu, atur jumlah, lalu kirim pesanan dari ponsel Anda.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <span class="badge">Meja Aktif</span>
            <span class="badge">Token: {{ \Illuminate\Support\Str::limit($tableSeat->qr_token, 12) }}</span>
        </div>
    </section>

    <section class="order-shell">
        <form id="customer-order-form" method="POST" action="{{ route('customer.orders.store', $tableSeat->qr_token) }}">
            @csrf

            <div class="grid grid-2 order-layout">
                <article class="card">
                    <div class="grid grid-2">
                        <div class="field">
                            <label for="customer_name">Nama Pemesan (opsional)</label>
                            <input id="customer_name" class="input" type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Contoh: Meja Keluarga Rahman">
                        </div>
                        <div class="field">
                            <label for="customer_note">Catatan Umum</label>
                            <input id="customer_note" class="input" type="text" name="customer_note" value="{{ old('customer_note') }}" placeholder="Tanpa es, less sugar, dll">
                        </div>
                    </div>

                    <h2 class="section-title">Pilih Menu</h2>
                    <div class="menu-grid">
                        @foreach ($menuItems as $menuItem)
                            <article class="menu-card {{ $menuItem->stock <= 0 ? 'menu-card-disabled' : '' }}">
                                <div class="menu-head">
                                    <h3>{{ $menuItem->name }}</h3>
                                    <span class="stock-chip {{ $menuItem->stock <= 0 ? 'stock-empty' : 'stock-ready' }}">
                                        {{ $menuItem->stock <= 0 ? 'Habis' : 'Stok: ' . $menuItem->stock }}
                                    </span>
                                </div>

                                <p class="muted">{{ $menuItem->description ?: 'Menu spesial hari ini.' }}</p>
                                <p class="price-tag">Rp{{ number_format($menuItem->price, 0, ',', '.') }}</p>

                                <div class="qty-control">
                                    <button
                                        class="qty-btn"
                                        type="button"
                                        data-target="qty-{{ $menuItem->id }}"
                                        data-step="-1"
                                        {{ $menuItem->stock <= 0 ? 'disabled' : '' }}
                                    >-</button>

                                    <input
                                        id="qty-{{ $menuItem->id }}"
                                        class="input qty-input"
                                        type="number"
                                        min="0"
                                        max="{{ $menuItem->stock }}"
                                        name="items[{{ $menuItem->id }}][quantity]"
                                        value="{{ old("items.{$menuItem->id}.quantity", 0) }}"
                                        data-menu-id="{{ $menuItem->id }}"
                                        data-menu-name="{{ $menuItem->name }}"
                                        data-menu-price="{{ $menuItem->price }}"
                                        {{ $menuItem->stock <= 0 ? 'disabled' : '' }}
                                    >

                                    <button
                                        class="qty-btn"
                                        type="button"
                                        data-target="qty-{{ $menuItem->id }}"
                                        data-step="1"
                                        {{ $menuItem->stock <= 0 ? 'disabled' : '' }}
                                    >+</button>
                                </div>

                                <div class="field" style="margin-bottom: 0;">
                                    <label for="note-{{ $menuItem->id }}">Catatan Item</label>
                                    <input
                                        id="note-{{ $menuItem->id }}"
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

                <aside class="card sticky-cart">
                    <h2 class="section-title">Ringkasan Pesanan</h2>
                    <p class="muted">Daftar item akan otomatis terisi saat jumlah menu diubah.</p>

                    <ul id="cart-preview" class="cart-preview list-clean">
                        <li class="muted" data-empty-cart>Belum ada item dipilih.</li>
                    </ul>

                    <div class="cart-total-wrap">
                        <div class="cart-total-row">
                            <span class="muted">Total Item</span>
                            <strong id="cart-total-items">0</strong>
                        </div>
                        <div class="cart-total-row">
                            <span class="muted">Estimasi Total</span>
                            <strong id="cart-total-price">Rp0</strong>
                        </div>
                    </div>

                    <button id="submit-order-btn" type="submit" class="btn btn-primary btn-block" disabled>Kirim Pesanan Sekarang</button>

                    <p class="footer-note">Sistem akan menolak pesanan jika stok tidak mencukupi saat submit.</p>
                </aside>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const qtyInputs = Array.from(document.querySelectorAll('.qty-input'));
            const previewElement = document.getElementById('cart-preview');
            const totalItemsElement = document.getElementById('cart-total-items');
            const totalPriceElement = document.getElementById('cart-total-price');
            const submitButton = document.getElementById('submit-order-btn');
            const rupiah = new Intl.NumberFormat('id-ID');

            const clampInput = (input) => {
                const min = Number(input.min || 0);
                const max = Number(input.max || Number.MAX_SAFE_INTEGER);
                const current = Number(input.value || 0);

                if (Number.isNaN(current)) {
                    input.value = String(min);
                    return;
                }

                input.value = String(Math.min(max, Math.max(min, current)));
            };

            const renderCart = () => {
                let totalItems = 0;
                let totalPrice = 0;

                const selectedItems = qtyInputs
                    .map((input) => {
                        const quantity = Number(input.value || 0);

                        return {
                            menuName: input.dataset.menuName,
                            menuPrice: Number(input.dataset.menuPrice || 0),
                            quantity,
                        };
                    })
                    .filter((item) => item.quantity > 0);

                previewElement.innerHTML = '';

                if (selectedItems.length === 0) {
                    const emptyNode = document.createElement('li');
                    emptyNode.className = 'muted';
                    emptyNode.textContent = 'Belum ada item dipilih.';
                    previewElement.appendChild(emptyNode);
                    submitButton.disabled = true;
                } else {
                    selectedItems.forEach((item) => {
                        totalItems += item.quantity;
                        totalPrice += item.menuPrice * item.quantity;

                        const row = document.createElement('li');
                        row.className = 'cart-item-row';

                        const left = document.createElement('span');
                        left.textContent = item.menuName + ' x' + item.quantity;

                        const right = document.createElement('strong');
                        right.textContent = 'Rp' + rupiah.format(item.menuPrice * item.quantity);

                        row.appendChild(left);
                        row.appendChild(right);
                        previewElement.appendChild(row);
                    });

                    submitButton.disabled = false;
                }

                totalItemsElement.textContent = String(totalItems);
                totalPriceElement.textContent = 'Rp' + rupiah.format(totalPrice);
            };

            document.querySelectorAll('.qty-btn').forEach((button) => {
                button.addEventListener('click', function () {
                    const input = document.getElementById(button.dataset.target);

                    if (!input || input.disabled) {
                        return;
                    }

                    const step = Number(button.dataset.step || 0);
                    const current = Number(input.value || 0);
                    input.value = String(current + step);
                    clampInput(input);
                    renderCart();
                });
            });

            qtyInputs.forEach((input) => {
                input.addEventListener('input', function () {
                    clampInput(input);
                    renderCart();
                });

                input.addEventListener('change', function () {
                    clampInput(input);
                    renderCart();
                });
            });

            renderCart();
        });
    </script>
@endpush
