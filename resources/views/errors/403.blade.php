@extends('layouts.app')

@section('title', 'Akses Ditolak')

@section('content')
    <section class="auth-wrap">
        <article class="card auth-card" style="text-align: center;">
            <span class="badge" style="margin-bottom: 10px;">HTTP 403</span>
            <h1>Akses Ditolak</h1>
            <p class="muted">Akun Anda tidak memiliki izin untuk membuka halaman atau aksi ini.</p>

            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 14px;">
                <a class="btn btn-soft" href="{{ route('dashboard.index') }}">Kembali ke Dashboard</a>
                <a class="btn btn-primary" href="{{ url()->previous() }}">Kembali ke Halaman Sebelumnya</a>
            </div>
        </article>
    </section>
@endsection
