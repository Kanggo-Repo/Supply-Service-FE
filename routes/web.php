<?php

use App\Http\Controllers\KeycloakAuthController;
use App\Http\Controllers\MaterialDonorController;
use App\Http\Controllers\MaterialManagementController;
use App\Http\Controllers\MaterialRecycleBinDonorController;
use App\Http\Controllers\ServiceAccessController;
use App\Http\Controllers\StoreDonorController;
use App\Http\Controllers\StoreLocationDonorController;
use App\Http\Controllers\StoreSearchRadiusSettingController;
use App\Http\Controllers\UnitManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [KeycloakAuthController::class, 'redirectToIdentityProvider'])->name('login');
Route::get('/auth/redirect', [KeycloakAuthController::class, 'redirectToIdentityProvider'])->name('auth.redirect');
Route::get('/auth/consume', [KeycloakAuthController::class, 'consume'])->name('auth.consume');
Route::post('/logout', [KeycloakAuthController::class, 'logout'])->name('logout');
Route::view('/profile', 'profile.show')->middleware(['platform.auth', 'service.access:supply'])->name('profile.show');
Route::get('/access-pending', [ServiceAccessController::class, 'pending'])
    ->middleware('platform.auth')
    ->name('service.access.pending');

Route::redirect('/', '/materials');

Route::middleware(['platform.auth', 'service.access:supply'])->group(function () {
    Route::get('/materials/type-suggestions', [MaterialManagementController::class, 'typeSuggestions'])
        ->middleware('supply.permission:materials.view')
        ->name('materials.type-suggestions');
    Route::get('/materials/tab/{type}', [MaterialManagementController::class, 'fetchTab'])
        ->middleware('supply.permission:materials.view')
        ->name('materials.tab');
    Route::get('/materials', [MaterialManagementController::class, 'index'])
        ->middleware('supply.permission:materials.view')
        ->name('materials.index');
    Route::get('/materials/create', [MaterialManagementController::class, 'create'])
        ->middleware('supply.permission:materials.create')
        ->name('materials.create');
    Route::post('/materials', [MaterialManagementController::class, 'store'])
        ->middleware('supply.permission:materials.create')
        ->name('materials.store');
    Route::get('/materials/{family}/{id}/edit', [MaterialManagementController::class, 'edit'])
        ->middleware('supply.permission:materials.update')
        ->name('materials.edit');
    Route::put('/materials/{family}/{id}', [MaterialManagementController::class, 'update'])
        ->middleware('supply.permission:materials.update')
        ->name('materials.update');
    Route::delete('/materials/{family}/{id}', [MaterialManagementController::class, 'destroy'])
        ->middleware('supply.permission:materials.delete')
        ->name('materials.destroy');

    Route::get('/materials/recycle-bin', [MaterialRecycleBinDonorController::class, 'index'])
        ->middleware('supply.permission:materials.recycle-bin.view')
        ->name('materials.recycle-bin');
    Route::post('/materials/{type}/{id}/restore', [MaterialRecycleBinDonorController::class, 'restore'])
        ->middleware('supply.permission:materials.recycle-bin.restore')
        ->name('materials.restore');
    Route::delete('/materials/{type}/{id}/force-delete', [MaterialRecycleBinDonorController::class, 'forceDelete'])
        ->middleware('supply.permission:materials.recycle-bin.delete')
        ->name('materials.force-delete');
    Route::post('/materials/bulk/restore', [MaterialRecycleBinDonorController::class, 'bulkRestore'])
        ->middleware('supply.permission:materials.recycle-bin.restore')
        ->name('materials.bulk-restore');
    Route::post('/materials/bulk/force-delete', [MaterialRecycleBinDonorController::class, 'bulkForceDelete'])
        ->middleware('supply.permission:materials.recycle-bin.delete')
        ->name('materials.bulk-force-delete');

    foreach ([
        'bricks',
        'cements',
        'nats',
        'sands',
        'cats',
        'ceramics',
        'steels',
        'kasa_gypsums',
        'paku_tembaks',
        'pakus',
    ] as $resource) {
        Route::get("/{$resource}/create", [MaterialDonorController::class, 'create'])
            ->middleware('supply.permission:materials.create')
            ->defaults('resource', $resource)
            ->name("{$resource}.create");
        Route::post("/{$resource}", [MaterialDonorController::class, 'store'])
            ->middleware('supply.permission:materials.create')
            ->defaults('resource', $resource)
            ->name("{$resource}.store");
        Route::get("/{$resource}/{id}", [MaterialDonorController::class, 'show'])
            ->middleware('supply.permission:materials.view')
            ->defaults('resource', $resource)
            ->name("{$resource}.show");
        Route::get("/{$resource}/{id}/edit", [MaterialDonorController::class, 'edit'])
            ->middleware('supply.permission:materials.update')
            ->defaults('resource', $resource)
            ->name("{$resource}.edit");
        Route::match(['put', 'patch'], "/{$resource}/{id}", [MaterialDonorController::class, 'update'])
            ->middleware('supply.permission:materials.update')
            ->defaults('resource', $resource)
            ->name("{$resource}.update");
        Route::post("/{$resource}/{id}/history/{historyLog}/restore", [MaterialDonorController::class, 'restoreHistory'])
            ->middleware('supply.permission:materials.update')
            ->defaults('resource', $resource)
            ->name("{$resource}.history.restore");

        Route::get("/api/{$resource}/field-values/{field}", [MaterialDonorController::class, 'fieldValues'])
            ->middleware('supply.permission:materials.view')
            ->defaults('resource', $resource)
            ->name("{$resource}.field-values");
        Route::delete("/api/v1/{$resource}/{id}", [MaterialDonorController::class, 'destroy'])
            ->middleware('supply.permission:materials.delete')
            ->defaults('resource', $resource)
            ->name("{$resource}.api-destroy");
        Route::get("/api/{$resource}/all-stores", [MaterialDonorController::class, 'allStores'])
            ->middleware('supply.permission:materials.view')
            ->defaults('resource', $resource)
            ->name("{$resource}.all-stores");
        Route::get("/api/{$resource}/addresses-by-store", [MaterialDonorController::class, 'addressesByStore'])
            ->middleware('supply.permission:materials.view')
            ->defaults('resource', $resource)
            ->name("{$resource}.addresses-by-store");
    }

    Route::get('/api/stores/all-stores', [MaterialDonorController::class, 'allStores'])
        ->middleware('supply.permission:materials.view')
        ->name('stores.all-stores');
    Route::get('/api/stores/addresses-by-store', [MaterialDonorController::class, 'addressesByStore'])
        ->middleware('supply.permission:materials.view')
        ->name('stores.addresses-by-store');
    Route::get('/api/stores/locations-by-store', [MaterialDonorController::class, 'locationsByStore'])
        ->middleware('supply.permission:materials.view')
        ->name('stores.locations-by-store');
    Route::post('/api/stores/quick-create', [MaterialDonorController::class, 'quickCreateStoreLocation'])
        ->middleware('supply.permission:materials.view')
        ->name('stores.quick-create');

    Route::get('/stores', [StoreDonorController::class, 'index'])
        ->middleware('supply.permission:stores.view')
        ->name('stores.index');
    Route::get('/stores/chunk', [StoreDonorController::class, 'fetchChunk'])
        ->middleware('supply.permission:stores.view')
        ->name('stores.chunk');
    Route::get('/stores/create', [StoreDonorController::class, 'create'])
        ->middleware('supply.permission:stores.create')
        ->name('stores.create');
    Route::post('/stores', [StoreDonorController::class, 'store'])
        ->middleware('supply.permission:stores.create')
        ->name('stores.store');
    Route::get('/stores/{store}', [StoreDonorController::class, 'show'])
        ->middleware('supply.permission:stores.view')
        ->name('stores.show');
    Route::get('/stores/{store}/edit', [StoreDonorController::class, 'edit'])
        ->middleware('supply.permission:stores.update')
        ->name('stores.edit');
    Route::put('/stores/{store}', [StoreDonorController::class, 'update'])
        ->middleware('supply.permission:stores.update')
        ->name('stores.update');
    Route::delete('/stores/{store}', [StoreDonorController::class, 'destroy'])
        ->middleware('supply.permission:stores.delete')
        ->name('stores.destroy');

    Route::get('/stores/{store}/locations/create', [StoreLocationDonorController::class, 'create'])
        ->middleware('supply.permission:stores.create')
        ->name('store-locations.create');
    Route::post('/stores/{store}/locations', [StoreLocationDonorController::class, 'store'])
        ->middleware('supply.permission:stores.create')
        ->name('store-locations.store');
    Route::get('/stores/{store}/locations/{location}/edit', [StoreLocationDonorController::class, 'edit'])
        ->middleware('supply.permission:stores.update')
        ->name('store-locations.edit');
    Route::put('/stores/{store}/locations/{location}', [StoreLocationDonorController::class, 'update'])
        ->middleware('supply.permission:stores.update')
        ->name('store-locations.update');
    Route::delete('/stores/{store}/locations/{location}', [StoreLocationDonorController::class, 'destroy'])
        ->middleware('supply.permission:stores.delete')
        ->name('store-locations.destroy');
    Route::get('/stores/{store}/locations/{location}/materials', [StoreLocationDonorController::class, 'materials'])
        ->middleware('supply.permission:stores.view')
        ->name('store-locations.materials');

    Route::get('/settings/store-search-radius', [StoreSearchRadiusSettingController::class, 'index'])
        ->middleware('supply.permission:store-search-radius.view')
        ->name('settings.store-search-radius.index');
    Route::post('/settings/store-search-radius', [StoreSearchRadiusSettingController::class, 'store'])
        ->middleware('supply.permission:store-search-radius.update')
        ->name('settings.store-search-radius.store');

    Route::get('/units', [UnitManagementController::class, 'index'])
        ->middleware('supply.permission:units.view')
        ->name('units.index');
    Route::get('/units/create', [UnitManagementController::class, 'create'])
        ->middleware('supply.permission:units.create')
        ->name('units.create');
    Route::post('/units', [UnitManagementController::class, 'store'])
        ->middleware('supply.permission:units.create')
        ->name('units.store');
    Route::get('/units/{id}/edit', [UnitManagementController::class, 'edit'])
        ->middleware('supply.permission:units.update')
        ->name('units.edit');
    Route::put('/units/{id}', [UnitManagementController::class, 'update'])
        ->middleware('supply.permission:units.update')
        ->name('units.update');
    Route::delete('/units/{id}', [UnitManagementController::class, 'destroy'])
        ->middleware('supply.permission:units.delete')
        ->name('units.destroy');
});
