@extends('layouts.auth')

@section('title', 'Login Supply FE')
@section('auth_kicker', 'Supply Admin Access')
@section('auth_brand_title', 'Portal Login Database Supply dan Jaringan Toko.')
@section('auth_brand_copy', 'Akses Supply FE menggunakan akun yang sama dengan monolith. Login dilakukan satu kali melalui monolith lalu session lokal FE akan dibuat otomatis untuk workspace supply.')
@section('auth_brand_footer', 'Copyright © ' . now()->year . ' Supply Service')
@section('auth_card_title', 'Masuk')
@section('auth_card_copy', 'Gunakan akun admin atau akun yang sudah diberi hak akses supply oleh admin di monolith.')

@section('auth_form')
    <div class="notice">
        <strong style="display:block; margin-bottom:6px;">Login Terpusat</strong>
        Login dari FE ini akan diarahkan ke monolith. Jika Anda masih punya sesi aktif di monolith, akses supply akan diteruskan tanpa perlu login ulang.
    </div>

    <p style="margin-bottom: 24px;">
        Setelah autentikasi berhasil, Supply FE akan membuat sesi lokal agar route guard, topbar actor, dan proxy request ke Supply BE berjalan stabil selama fase transisi.
    </p>

    <a href="{{ route('auth.redirect') }}" class="cta">
        Masuk dengan Akun Monolith
    </a>
@endsection

@section('auth_footer')
    <p style="margin-top: 20px; font-size: 14px;">
        Jika akses ditolak, pastikan akun Anda aktif di monolith dan sudah memiliki permission supply yang sesuai.
    </p>
@endsection
