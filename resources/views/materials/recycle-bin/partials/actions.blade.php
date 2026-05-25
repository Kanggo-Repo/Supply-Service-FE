@php
    $permissionGate = app(\App\Support\Auth\SupplyPermissionGate::class);
    $canForceDelete = $permissionGate->allows(auth()->user(), 'materials.recycle-bin.delete');
    $canRestore = $permissionGate->allows(auth()->user(), 'materials.recycle-bin.restore')
        || (int) data_get($item, 'deleted_by.id', 0) === (int) auth()->id();
@endphp

<div class="btn-group-compact justify-center">
    @if($canForceDelete)
        <form
            method="POST"
            action="{{ route('materials.force-delete', ['type' => $item->material_type, 'id' => $item->id]) }}"
            class="inline"
            data-confirm="Hapus permanen? Tindakan ini tidak bisa dibatalkan."
            data-confirm-title="Hapus Permanen"
            data-confirm-type="danger"
            data-confirm-ok="Ya, Hapus"
            data-confirm-cancel="Batal">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-action" title="Hapus permanen">
                <i class="bi bi-trash"></i>
                <span class="sr-only">Hapus permanen</span>
            </button>
        </form>
    @endif

    @if($canRestore)
        <form method="POST" action="{{ route('materials.restore', ['type' => $item->material_type, 'id' => $item->id]) }}" class="inline">
            @csrf
            <button type="submit" class="btn btn-success btn-action" title="Recycle (restore)">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span class="sr-only">Recycle (restore)</span>
            </button>
        </form>
    @endif
</div>
