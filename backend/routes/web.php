<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('csrf', [AccountController::class, 'csrf']);
    Route::middleware('throttle:8,1')->group(function () {
        Route::post('login', [AccountController::class, 'login']);
        Route::post('cadastro', [AccountController::class, 'store']);
    });
    Route::post('logout', [AccountController::class, 'logout']);
    Route::middleware(['access', 'auth.session', 'permission'])->group(function () {
        Route::get('eu', [AccountController::class, 'show'])->name('account.show');
        Route::get('categorias', [CategoryController::class, 'index'])->name('category.index');
        Route::get('campanhas', [CampaignController::class, 'index'])->name('campaign.index');
        Route::get('campanhas/{id}', [CampaignController::class, 'show'])->whereNumber('id')->name('campaign.show');
        Route::get('organizadores/{id}', [OrganizerController::class, 'show'])->whereNumber('id')->name('organizer.show');
        Route::get('fotos/{name}', [CampaignController::class, 'photo'])->name('campaign.photo');
        Route::get('area', [AreaController::class, 'index'])->name('area.index');
        Route::get('estudio', [AreaController::class, 'studio'])->name('studio.index');
        Route::post('verificacao', [OrganizerController::class, 'store'])->name('organizer.store');
        Route::post('campanhas/{id}/reservar', [ReservationController::class, 'store'])->whereNumber('id')->name('reservation.store');
        Route::post('reservas/{id}/confirmar', [ReservationController::class, 'confirm'])->whereNumber('id')->name('reservation.confirm');
        Route::post('estudio/campanhas', [CampaignController::class, 'store'])->name('campaign.store');
        Route::post('estudio/campanhas/{id}', [CampaignController::class, 'update'])->whereNumber('id')->name('campaign.update');
        Route::post('estudio/campanhas/{id}/status', [CampaignController::class, 'change'])->whereNumber('id')->name('campaign.change');
        Route::prefix('admin')->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('admin.index');
            Route::get('documentos/{id}', [OrganizerController::class, 'document'])->whereNumber('id')->name('organizer.document');
            Route::post('organizadores/{id}', [OrganizerController::class, 'decide'])->whereNumber('id')->name('organizer.decide');
            Route::post('campanhas/{id}', [AdminController::class, 'campaign'])->whereNumber('id')->name('campaign.decide');
            Route::post('usuarios/{id}', [AdminController::class, 'suspend'])->whereNumber('id')->name('user.suspend');
            Route::post('categorias', [CategoryController::class, 'store'])->name('category.store');
            Route::post('categorias/{id}', [CategoryController::class, 'update'])->whereNumber('id')->name('category.update');
            Route::delete('categorias/{id}', [CategoryController::class, 'destroy'])->whereNumber('id')->name('category.destroy');
        });
    });
});
