<?php
use App\Http\Controllers\MasterData\BusinessPartnerAddressController;
use App\Http\Controllers\MasterData\ItemAliasController;
use App\Http\Controllers\MasterData\ItemUomController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('master')->group(function () {
    Route::post('/partner-addresses/{type}/{id}', [BusinessPartnerAddressController::class, 'store'])
        ->where('type', 'customer|vendor')->whereNumber('id')->name('master.partner-addresses.store');
    Route::put('/partner-addresses/{type}/{id}/{address}', [BusinessPartnerAddressController::class, 'update'])
        ->where('type', 'customer|vendor')->whereNumber('id')->whereNumber('address')->name('master.partner-addresses.update');
    Route::delete('/partner-addresses/{type}/{id}/{address}', [BusinessPartnerAddressController::class, 'destroy'])
        ->where('type', 'customer|vendor')->whereNumber('id')->whereNumber('address')->name('master.partner-addresses.destroy');

    Route::post('/items/{item}/uoms', [ItemUomController::class, 'store'])->name('master.item-uoms.store');
    Route::put('/items/{item}/uoms/{uomLevel}', [ItemUomController::class, 'update'])->name('master.item-uoms.update');
    Route::delete('/items/{item}/uoms/{uomLevel}', [ItemUomController::class, 'destroy'])->name('master.item-uoms.destroy');

    Route::post('/items/{item}/aliases', [ItemAliasController::class, 'store'])->name('master.item-aliases.store');
    Route::put('/items/{item}/aliases/{alias}', [ItemAliasController::class, 'update'])->name('master.item-aliases.update');
    Route::delete('/items/{item}/aliases/{alias}', [ItemAliasController::class, 'destroy'])->name('master.item-aliases.destroy');
});
