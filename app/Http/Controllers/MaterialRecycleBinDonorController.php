<?php

namespace App\Http\Controllers;

use App\Services\Supply\MaterialRecycleBinDonorService;
use App\Services\Supply\SupplyServiceClient;
use App\Support\Auth\SupplyPermissionGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class MaterialRecycleBinDonorController extends Controller
{
    public function __construct(
        private readonly MaterialRecycleBinDonorService $donorService,
        private readonly SupplyServiceClient $supplyServiceClient,
        private readonly SupplyPermissionGate $permissionGate,
    ) {}

    public function index(Request $request): View
    {
        return view('materials.recycle-bin.index', $this->donorService->buildPageData(
            $request->user(),
            $request->query('tab'),
        ));
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        $item = $this->donorService->findDeletedItem($type, $id, $request->user());
        if (! $item) {
            return back()->with('error', 'Material tidak ditemukan di recycle bin.');
        }

        if (! $this->canRestore($request, $item)) {
            abort(403, 'Anda tidak memiliki izin untuk restore material ini.');
        }

        try {
            $this->supplyServiceClient->restoreRecycledMaterial($type, $id, $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Gagal restore material: '.$exception->getMessage());
        }

        $materialName = trim((string) ($item['label'] ?? $item['material_name'] ?? $item['brand'] ?? 'unknown'));

        return back()->with('success', "Material {$materialName} berhasil di-restore dari recycle bin.");
    }

    public function forceDelete(Request $request, string $type, int $id): RedirectResponse
    {
        if (! $this->permissionGate->allows($request->user(), 'materials.recycle-bin.delete')) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus material secara permanen.');
        }

        try {
            $this->supplyServiceClient->forceDeleteRecycledMaterial($type, $id, $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Gagal menghapus material: '.$exception->getMessage());
        }

        return back()->with('success', 'Material berhasil dihapus secara permanen dari recycle bin.');
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $items = collect((array) $request->input('items', []))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->values();

        if ($items->isEmpty()) {
            return back()->with('error', 'Tidak ada material yang dipilih.');
        }

        $deletedIndex = collect((array) ($this->supplyServiceClient->materialRecycleBin($request->user())['data']['items'] ?? []))
            ->filter(fn (mixed $row): bool => is_array($row))
            ->mapWithKeys(function (array $row): array {
                $type = (string) ($row['material_type'] ?? $row['family'] ?? '');
                $id = (int) ($row['id'] ?? 0);

                return [$type.':'.$id => $row];
            });

        $restored = 0;
        $failed = 0;

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? '');
            $id = (int) ($item['id'] ?? 0);

            if ($type === '' || $id <= 0) {
                $failed++;

                continue;
            }

            $deletedItem = $deletedIndex->get($type.':'.$id);
            if (! is_array($deletedItem) || ! $this->canRestore($request, $deletedItem)) {
                $failed++;

                continue;
            }

            try {
                $this->supplyServiceClient->restoreRecycledMaterial($type, $id, $request->user());
                $restored++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        $message = "Restored {$restored} material(s)";
        if ($failed > 0) {
            $message .= ", {$failed} failed.";
        }

        return back()->with('success', $message);
    }

    public function bulkForceDelete(Request $request): RedirectResponse
    {
        if (! $this->permissionGate->allows($request->user(), 'materials.recycle-bin.delete')) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus material secara permanen.');
        }

        $items = collect((array) $request->input('items', []))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->values();

        if ($items->isEmpty()) {
            return back()->with('error', 'Tidak ada material yang dipilih.');
        }

        $deleted = 0;
        $failed = 0;

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? '');
            $id = (int) ($item['id'] ?? 0);

            if ($type === '' || $id <= 0) {
                $failed++;

                continue;
            }

            try {
                $this->supplyServiceClient->forceDeleteRecycledMaterial($type, $id, $request->user());
                $deleted++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        $message = "Deleted {$deleted} material(s) permanently";
        if ($failed > 0) {
            $message .= ", {$failed} failed.";
        }

        return back()->with('success', $message);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function canRestore(Request $request, array $item): bool
    {
        if ($this->permissionGate->allows($request->user(), 'materials.recycle-bin.restore')) {
            return true;
        }

        return (int) data_get($item, 'deleted_by.id', 0) === (int) $request->user()?->getAuthIdentifier();
    }
}
