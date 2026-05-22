<div class="btn-group-compact justify-center">
    @if(auth()->user()->can('materials.recycle-bin.delete'))
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

    <form method="POST" action="{{ route('materials.restore', ['type' => $item->material_type, 'id' => $item->id]) }}" class="inline">
        @csrf
        <button type="submit" class="btn btn-success btn-action" title="Recycle (restore)">
            <i class="bi bi-arrow-counterclockwise"></i>
            <span class="sr-only">Recycle (restore)</span>
        </button>
    </form>
</div>
