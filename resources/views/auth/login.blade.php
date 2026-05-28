@extends('layouts.auth')

@section('title', 'Login Supply FE')
@section('auth_kicker', 'Supply Admin Access')
@section('auth_brand_title', 'Portal Login Database Supply dan Jaringan Toko.')
@section('auth_brand_copy', 'Akses Supply FE menggunakan akun Keycloak yang sama dengan platform dan calculation. Login dipusatkan di SSO agar perpindahan antar service tidak perlu login ulang.')
@section('auth_brand_footer', 'Copyright © ' . now()->year . ' Supply Service')
@section('auth_card_title', 'Masuk')
@section('auth_card_copy', 'Gunakan akun yang sudah didaftarkan di Keycloak dan sudah diberi akses ke modul supply oleh administrator platform.')

@section('auth_form')
    <div class="notice">
        <strong style="display:block; margin-bottom:6px;">Login Terpusat</strong>
        Login dari FE ini akan diarahkan ke Keycloak. Jika Anda masih punya sesi aktif di SSO, akses supply akan diteruskan tanpa perlu memasukkan password lagi.
    </div>

    <p style="margin-bottom: 24px;">
        Setelah autentikasi berhasil, Supply FE akan membuat sesi lokal dan memeriksa access matrix dari platform-service agar hanya user yang punya akses supply yang bisa masuk ke workspace ini.
    </p>

    <a href="{{ route('auth.redirect') }}" class="cta">
        Masuk dengan Keycloak
    </a>
@endsection

@section('auth_footer')
    <p style="margin-top: 20px; font-size: 14px;">
        Jika akses ditolak, pastikan akun Anda aktif di Keycloak dan sudah memiliki akses service supply di platform-service.
    </p>
@endsection
