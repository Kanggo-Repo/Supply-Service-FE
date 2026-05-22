@extends('layouts.app')

@section('title', 'Stores | Supply FE')
@section('page_title', 'Store Workspace')
@section('page_copy', 'Ringkasan network toko hidup di Supply BE. Halaman ini dipakai untuk memvalidasi auth bridge dan trusted proxy FE sebelum donor UI store penuh dipasang.')

@section('content')
    <div class="grid">
        @if ($error)
            <div class="alert">
                Gagal memuat data store dari Supply BE: {{ $error }}
            </div>
        @endif

        <section class="cards">
            <article class="card">
                <small>Total Stores</small>
                <strong>{{ $storeTotal }}</strong>
                <span style="color: var(--muted);">Jumlah store terdaftar di owner service supply.</span>
            </article>
            <article class="card">
                <small>Workspace Status</small>
                <strong>Connected</strong>
                <span style="color: var(--muted);">Halaman ini sudah mengambil data dari private BE.</span>
            </article>
        </section>

        <section class="panel">
            <div class="section-title">
                <div>
                    <h2 style="margin: 0;">Store Summary</h2>
                    <p style="margin: 6px 0 0; color: var(--muted);">Menampilkan payload utama store beserta jumlah lokasi dan material availability yang sudah dibentuk di backend.</p>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Store</th>
                        <th>Locations</th>
                        <th>Availabilities</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stores as $store)
                        <tr>
                            <td>{{ $store['name'] ?? 'Store' }}</td>
                            <td>{{ $store['location_count'] ?? 0 }}</td>
                            <td>{{ $store['material_availability_count'] ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="color: var(--muted);">Belum ada store yang tampil dari Supply BE.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
