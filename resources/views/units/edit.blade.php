@include('units.partials.form', [
    'action' => route('units.update', $unit->id),
    'method' => 'PUT',
    'submitLabel' => 'Update',
    'selectedTypes' => old('material_types', $selectedTypes ?? []),
    'unit' => $unit,
])
