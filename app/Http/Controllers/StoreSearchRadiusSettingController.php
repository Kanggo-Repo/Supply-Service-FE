<?php

namespace App\Http\Controllers;

use App\Services\Supply\SupplyServiceClient;
use App\Services\Supply\SupplyServiceValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class StoreSearchRadiusSettingController extends Controller
{
    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
    ) {}

    public function index(Request $request): View
    {
        $payload = $this->supplyServiceClient->storeSearchRadiusSettings($request->user());
        $data = (array) ($payload['data'] ?? []);

        return view('settings.store_search_radius.index', [
            'projectStoreRadiusDefaultKm' => (float) ($data['project_store_radius_default_km'] ?? 10),
            'projectStoreRadiusFinalKm' => (float) ($data['project_store_radius_final_km'] ?? 15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $this->supplyServiceClient->updateStoreSearchRadiusSettings([
                'project_store_radius_default_km' => $request->input('project_store_radius_default_km'),
                'project_store_radius_final_km' => $request->input('project_store_radius_final_km'),
            ], $request->user());
        } catch (SupplyServiceValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui setting radius toko: '.$exception->getMessage());
        }

        return redirect()
            ->route('settings.store-search-radius.index')
            ->with('success', 'Setting radius pencarian toko berhasil diperbarui.');
    }
}
