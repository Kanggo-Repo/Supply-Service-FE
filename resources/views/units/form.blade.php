@extends('layouts.app')

@section('title', ($mode === 'create' ? 'Tambah' : 'Edit') . ' Unit | Supply FE')
@section('page_title', $mode === 'create' ? 'Tambah Unit' : 'Edit Unit')
@section('page_copy', 'Form unit donor dari monolith, disederhanakan menjadi page penuh dan menulis ke owner API Supply BE.')

@section('content')
    <section class="panel">
        <div class="section-title">
            <div>
                <h2 style="margin: 0;">{{ $mode === 'create' ? 'Form Unit Baru' : ($unit['name'] ?? 'Form Unit') }}</h2>
                <p style="margin: 6px 0 0; color: var(--muted);">Tetapkan code, weight, dan material types yang menggunakan unit ini.</p>
            </div>
            <a href="{{ route('units.index') }}" class="pill" style="text-decoration:none;">Kembali ke List</a>
        </div>

        @if (session('error'))
            <div class="alert" style="margin-bottom: 18px;">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ $mode === 'create' ? route('units.store') : route('units.update', ['id' => $unit['id']]) }}" style="display:grid; gap:18px;">
            @csrf
            @if ($mode === 'edit')
                @method('PUT')
            @endif

            <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:16px;">
                <div>
                    <label for="unit_code" style="display:block; font-weight:700; margin-bottom:8px;">Code</label>
                    <input id="unit_code" name="code" value="{{ old('code', $unit['code'] ?? '') }}" class="form-control-inline">
                    @error('code')<div style="color:#b91c1c; font-size:13px; margin-top:6px;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="unit_name" style="display:block; font-weight:700; margin-bottom:8px;">Name</label>
                    <input id="unit_name" name="name" value="{{ old('name', $unit['name'] ?? '') }}" class="form-control-inline">
                    @error('name')<div style="color:#b91c1c; font-size:13px; margin-top:6px;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="unit_weight" style="display:block; font-weight:700; margin-bottom:8px;">Package Weight</label>
                    <input id="unit_weight" type="number" step="0.01" name="package_weight" value="{{ old('package_weight', $unit['package_weight'] ?? '') }}" class="form-control-inline">
                    @error('package_weight')<div style="color:#b91c1c; font-size:13px; margin-top:6px;">{{ $message }}</div>@enderror
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="unit_description" style="display:block; font-weight:700; margin-bottom:8px;">Description</label>
                    <textarea id="unit_description" name="description" rows="3" class="form-control-inline">{{ old('description', $unit['description'] ?? '') }}</textarea>
                    @error('description')<div style="color:#b91c1c; font-size:13px; margin-top:6px;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div>
                <strong style="display:block; margin-bottom:12px;">Material Types</strong>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
                    @php
                        $selectedMaterialTypes = old('material_types', $unit['material_types'] ?? []);
                    @endphp
                    @foreach ($materialTypes as $type)
                        <label style="display:flex; align-items:center; gap:10px; padding: 12px 14px; border:1px solid var(--line); border-radius:14px; background:#fff;">
                            <input type="checkbox" name="material_types[]" value="{{ $type['value'] }}" @checked(in_array($type['value'], $selectedMaterialTypes, true))>
                            <span>{{ $type['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('material_types')<div style="color:#b91c1c; font-size:13px; margin-top:6px;">{{ $message }}</div>@enderror
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <a href="{{ route('units.index') }}" class="pill" style="text-decoration:none; background: rgba(100,116,139,0.12); color:#475569;">Batal</a>
                <button type="submit" class="pill" style="border:none; cursor:pointer;">
                    {{ $mode === 'create' ? 'Simpan Unit' : 'Update Unit' }}
                </button>
            </div>
        </form>
    </section>

    <style>
        .form-control-inline {
            width: 100%;
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 14px;
            padding: 12px 14px;
            color: var(--ink);
            font: inherit;
        }
        textarea.form-control-inline {
            resize: vertical;
            min-height: 92px;
        }
        @media (max-width: 900px) {
            form > div:first-of-type {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endsection
