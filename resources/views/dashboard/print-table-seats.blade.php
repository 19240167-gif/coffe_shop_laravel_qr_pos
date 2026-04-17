<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak QR Meja</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Sora:wght@400;500;600;700&display=swap');

        :root {
            --ink: #2d1e15;
            --ink-soft: #5f4a3e;
            --line: rgba(125, 61, 34, 0.22);
            --card: #fff;
            --accent: #7d3d22;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Sora', sans-serif;
            color: var(--ink);
            background: #f5ede4;
        }

        .shell {
            width: min(1140px, 94vw);
            margin: 0 auto;
            padding: 18px 0 36px;
        }

        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(6px);
        }

        .print-toolbar h1 {
            margin: 0;
            font-family: 'Fraunces', serif;
            font-size: clamp(1.2rem, 2.7vw, 1.7rem);
        }

        .btn {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 9px 14px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            font-size: 0.86rem;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(120deg, #6b2f1a, var(--accent));
        }

        .grid {
            margin-top: 16px;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        }

        .ticket {
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 12px;
            background: var(--card);
            break-inside: avoid;
        }

        .ticket h2 {
            margin: 0;
            font-family: 'Fraunces', serif;
            font-size: 1.2rem;
        }

        .meta {
            margin: 6px 0 10px;
            color: var(--ink-soft);
            font-size: 0.78rem;
            word-break: break-all;
        }

        .qr-wrap {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            display: inline-flex;
            padding: 8px;
        }

        .scan-url {
            margin-top: 8px;
            font-size: 0.76rem;
            color: var(--ink-soft);
            word-break: break-all;
        }

        .state {
            display: inline-block;
            margin-top: 8px;
            font-size: 0.7rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border-radius: 999px;
            padding: 4px 8px;
            border: 1px solid var(--line);
        }

        @page {
            size: A4;
            margin: 10mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .print-toolbar {
                display: none;
            }

            .shell {
                width: 100%;
                margin: 0;
                padding: 0;
            }

            .grid {
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="print-toolbar">
            <div>
                <h1>Cetak QR Meja Massal</h1>
                <small>Total meja: {{ $tableSeats->count() }}</small>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" onclick="window.print()">Cetak Sekarang</button>
                <a class="btn" href="{{ route('dashboard.index') }}">Kembali</a>
            </div>
        </section>

        <section class="grid">
            @forelse ($tableSeats as $tableSeat)
                <article class="ticket">
                    <h2>Meja {{ $tableSeat->code }}</h2>
                    <p class="meta">Token: {{ $tableSeat->qr_token }}</p>
                    <div class="qr-wrap">
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(160)->margin(1)->generate(route('customer.table', $tableSeat->qr_token)) !!}
                    </div>
                    <p class="scan-url">{{ route('customer.table', $tableSeat->qr_token) }}</p>
                    <span class="state">{{ $tableSeat->is_active ? 'Active' : 'Inactive' }}</span>
                </article>
            @empty
                <article class="ticket">
                    <h2>Belum ada meja</h2>
                    <p class="meta">Tambahkan meja terlebih dahulu dari dashboard.</p>
                </article>
            @endforelse
        </section>
    </main>
</body>
</html>
