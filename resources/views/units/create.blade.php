@include('units.partials.form', [
    'action' => route('units.store'),
    'submitLabel' => 'Simpan',
    'selectedTypes' => old('material_types', []),
])
