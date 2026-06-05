<?php

namespace App\Http\Controllers;

use App\Services\Supply\SupplyServiceClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Throwable;

class SupplyWorkspaceController extends Controller
{
    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
    ) {}

    public function stores(Request $request): View
    {
        $payload = [
            'data' => [],
            'total' => 0,
        ];
        $error = null;

        try {
            $payload = $this->supplyServiceClient->stores($request->user());
        } catch (Throwable $exception) {
            report($exception);
            $error = $exception->getMessage();
        }

        return view('workspace.stores', [
            'activeNav' => 'stores',
            'error' => $error,
            'stores' => is_array($payload['data'] ?? null) ? $payload['data'] : [],
            'storeTotal' => (int) ($payload['total'] ?? 0),
        ]);
    }
}
