@extends('layouts.app')

@section('title', 'Login Kasir/Admin')

@section('content')
    <section class="auth-wrap">
        <article class="card auth-card">
            <h1>Masuk Dashboard</h1>
            <p class="muted">Gunakan akun staf untuk mengelola menu, stok, dan order pelanggan.</p>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" class="input" type="password" name="password" required>
                </div>

                <label class="remember-row">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ingat saya</span>
                </label>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <p class="footer-note">Demo: admin@beanflow.local / password atau kasir@beanflow.local / password</p>
        </article>
    </section>
@endsection
