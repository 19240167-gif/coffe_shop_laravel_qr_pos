# Coffee Shop Laravel QR POS

Aplikasi kasir restoran berbasis Laravel dengan dua antarmuka utama:

- Dashboard admin/kasir untuk manajemen menu, stok, meja QR, dan status pesanan.
- Halaman pelanggan berbasis QR per meja untuk pemesanan instan via smartphone.

## Fitur Utama

- Manajemen menu: nama, slug, harga, stok, status aktif.
- Manajemen stok: tambah, kurangi, dan set langsung (dengan histori pergerakan stok).
- Manajemen meja: kode meja dan token QR unik otomatis.
- Pemesanan pelanggan: pilih item, jumlah, catatan, kirim pesanan.
- Workflow pesanan: pending, confirmed, preparing, ready, completed, cancelled.
- Real-time ready: event pesanan baru dan perubahan status via broadcasting (Pusher-compatible).
- Dashboard realtime tanpa reload: kartu order akan sinkron otomatis saat event baru masuk.
- Audit log aktivitas: jejak aksi penting user tersimpan dan tampil di dashboard.
- UI responsive: layout mobile-first untuk pelanggan dan dashboard kasir.

## Struktur Domain Data

- `users`: user admin/kasir dengan kolom role.
- `menu_items`: data menu dan stok.
- `table_seats`: data meja dan token QR.
- `orders`: header pesanan.
- `order_items`: detail item pesanan.
- `stock_movements`: histori penyesuaian stok.

## Setup Cepat

1. Install dependency backend:

```bash
composer install
```

2. Install dependency frontend:

```bash
npm install
```

3. Buat file environment:

```bash
copy .env.example .env
php artisan key:generate
```

4. Atur koneksi database MySQL pada `.env`, lalu jalankan:

```bash
php artisan migrate --seed
```

5. Jalankan server aplikasi:

```bash
php artisan serve
npm run dev
```

Buka:

- Halaman login staf: `http://127.0.0.1:8000/login`
- Dashboard kasir (butuh login): `http://127.0.0.1:8000/dashboard`
- Link pelanggan per meja: tersedia di panel `Daftar Meja` pada dashboard
- Print QR meja massal (admin): tombol `Print QR Massal` tersedia di panel `Daftar Meja`

## Demo Akun Seed

Setelah `php artisan migrate --seed`:

- Admin: `admin@beanflow.local` / `password`
- Kasir: `kasir@beanflow.local` / `password`

## Hak Akses Role

- Admin: akses penuh dashboard termasuk tambah menu, tambah meja, dan penyesuaian stok.
- Kasir: fokus operasional order (lihat order masuk dan update status/pembayaran).
- Jika user tidak memiliki izin pada aksi tertentu, sistem akan menampilkan halaman `403`.

## Aktivasi Real-Time Pusher

Secara default, `.env.example` memakai `BROADCAST_DRIVER=log` agar aman untuk lokal.
Untuk real-time penuh:

1. Ganti di `.env`:

```env
BROADCAST_DRIVER=pusher
```

2. Isi kredensial Pusher:

```env
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=...
```

3. Restart aplikasi dan Vite.

Dashboard akan berlangganan channel `orders` menggunakan Laravel Echo.

## Catatan Pengembangan

- Event real-time yang tersedia:
  - `order.created`
  - `order.status-updated`
- Event akan tetap aman dijalankan meski broadcaster masih `log`.
