@extends('layouts.app')

@section('title', 'Materials | Supply FE')
@section('page_title', 'Material Workspace')
@section('page_copy', 'Bootstrap awal supply FE untuk memvalidasi auth bridge, route guard, dan koneksi ke Supply BE sebelum donor UI material penuh dipindahkan.')

@section('content')
    <div class="grid">
        @if ($error)
            <div class="alert">
                Gagal memuat reference material dari Supply BE: {{ $error }}
            </div>
        @endif

        <section class="cards">
            @foreach ($materialGroups as $group)
                <article class="card">
                    <small>{{ strtoupper(str_replace('_', ' ', $group['family'])) }}</small>
                    <strong>{{ $group['count'] }}</strong>
                    <span style="color: var(--muted);">material terbaca dari unified reference API.</span>
                </article>
            @endforeach
        </section>

        <section class="panel">
            <div class="section-title">
                <div>
                    <h2 style="margin: 0;">Snapshot Material per Family</h2>
                    <p style="margin: 6px 0 0; color: var(--muted);">Daftar ini masih read-only. Wave donor UI berikutnya akan mengganti shell ini dengan admin flow material penuh.</p>
                </div>
            </div>

            @forelse ($materialGroups as $group)
                <div style="padding: 18px 0; border-top: 1px solid rgba(213,223,235,0.8);">
                    <div class="section-title" style="margin-bottom: 10px;">
                        <strong>{{ strtoupper(str_replace('_', ' ', $group['family'])) }}</strong>
                        <span class="pill">{{ $group['count'] }} item</span>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['items'] as $item)
                                <tr>
                                    <td>{{ $item['label'] ?? $item['brand'] ?? 'Material' }}</td>
                                    <td>#{{ $item['id'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p style="margin: 0; color: var(--muted);">Belum ada material yang berhasil dibaca dari Supply BE.</p>
            @endforelse
        </section>
    </div>
@endsection
