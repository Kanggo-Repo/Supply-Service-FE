@extends('layouts.app')

@section('title', ($mode === 'create' ? 'Tambah' : 'Edit') . ' Material | Supply FE')
@section('page_title', $mode === 'create' ? 'Tambah ' . $familyMeta['label'] : 'Edit ' . $familyMeta['label'])
@section('page_copy', 'Form ini mengirim payload langsung ke owner API Supply BE. Field mengikuti writable attributes per family material.')

@section('content')
    <section class="panel">
        <div class="section-title">
            <div>
                <h2 style="margin: 0;">{{ $mode === 'create' ? 'Form Material Baru' : ($material['label'] ?? 'Form Material') }}</h2>
                <p style="margin: 6px 0 0; color: var(--muted);">{{ $familyMeta['description'] }}</p>
            </div>
            <a href="{{ route('materials.index', ['family' => $family]) }}" class="pill" style="text-decoration: none;">Kembali ke List</a>
        </div>

        <form method="POST" action="{{ $mode === 'create' ? route('materials.store') : route('materials.update', ['family' => $family, 'id' => $material['id']]) }}" style="display:grid; gap:18px;">
            @csrf
            @if ($mode === 'edit')
                @method('PUT')
            @endif
            <input type="hidden" name="family" value="{{ $family }}">

            <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:16px;">
                @foreach ($fields as $field => $definition)
                    <div style="{{ $definition['type'] === 'textarea' ? 'grid-column: 1 / -1;' : '' }}">
                        <label for="field_{{ $field }}" style="display:block; font-weight:700; margin-bottom:8px;">{{ $definition['label'] }}</label>

                        @if ($definition['type'] === 'textarea')
                            <textarea id="field_{{ $field }}" name="{{ $field }}" rows="3" class="form-control-inline">{{ old($field, $material[$field] ?? '') }}</textarea>
                        @else
                            <input id="field_{{ $field }}"
                                   name="{{ $field }}"
                                   type="{{ in_array($definition['type'], ['number', 'decimal'], true) ? 'number' : 'text' }}"
                                   step="{{ $definition['step'] ?? '0.01' }}"
                                   value="{{ old($field, $material[$field] ?? '') }}"
                                   class="form-control-inline">
                        @endif

                        @error($field)
                            <div style="color:#b91c1c; font-size:13px; margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <a href="{{ route('materials.index', ['family' => $family]) }}" class="pill" style="text-decoration:none; background: rgba(100,116,139,0.12); color:#475569;">Batal</a>
                <button type="submit" class="pill" style="border:none; cursor:pointer;">
                    {{ $mode === 'create' ? 'Simpan Material' : 'Update Material' }}
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
