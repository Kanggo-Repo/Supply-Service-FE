@extends('layouts.app')

@section('title', 'Units | Supply FE')
@section('page_title', 'Unit Workspace')
@section('page_copy', 'Bootstrap halaman unit untuk memastikan grouped unit payload dari Supply BE sudah bisa dipakai oleh FE supply.')

@section('content')
    <div class="grid">
        @if ($error)
            <div class="alert">
                Gagal memuat grouped units dari Supply BE: {{ $error }}
            </div>
        @endif

        <section class="panel">
            <div class="section-title">
                <div>
                    <h2 style="margin: 0;">Grouped Units</h2>
                    <p style="margin: 6px 0 0; color: var(--muted);">Payload grouped ini akan menjadi fondasi donor UI unit management pada wave berikutnya.</p>
                </div>
            </div>

            @forelse ($unitGroups as $materialType => $units)
                <div style="padding: 18px 0; border-top: 1px solid rgba(213,223,235,0.8);">
                    <div class="section-title" style="margin-bottom: 10px;">
                        <strong>{{ strtoupper(str_replace('_', ' ', (string) $materialType)) }}</strong>
                        <span class="pill">{{ is_array($units) ? count($units) : 0 }} unit</span>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Weight</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ((array) $units as $unit)
                                <tr>
                                    <td>{{ $unit['name'] ?? 'Unit' }}</td>
                                    <td>{{ $unit['code'] ?? '-' }}</td>
                                    <td>{{ $unit['package_weight'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p style="margin: 0; color: var(--muted);">Belum ada grouped unit yang berhasil dimuat.</p>
            @endforelse
        </section>
    </div>
@endsection
