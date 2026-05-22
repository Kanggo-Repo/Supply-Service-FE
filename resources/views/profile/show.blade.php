@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="card">
        <h3 style="margin-top: 0;">Profile</h3>
        <p style="margin-bottom: 8px;"><strong>Nama:</strong> {{ auth()->user()?->name }}</p>
        <p style="margin-bottom: 8px;"><strong>Email:</strong> {{ auth()->user()?->email }}</p>
        <p style="margin-bottom: 0;"><strong>Auth Subject:</strong> {{ auth()->user()?->auth_subject ?: '-' }}</p>
    </div>
@endsection
