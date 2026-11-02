<?php

use App\Http\Controllers\AuctionListingController;
use App\Http\Controllers\BidController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletTopUpController;
use App\Livewire\AuctionDashboard;
use App\Livewire\ListingDetail;
use Illuminate\Support\Facades\Route;

Route::get('/', AuctionDashboard::class)->name('dashboard');
Route::get('/listings/{listing}', ListingDetail::class)->name('listings.show');

Route::prefix('api/v1')->name('api.v1.')->group(function (): void {
    Route::get('/demo/dashboard', [AuctionListingController::class, 'dashboard'])->name('demo.dashboard');
    Route::get('/listings', [AuctionListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/{listing}', [AuctionListingController::class, 'show'])->name('listings.show');
    Route::get('/listings/{listing}/bids', [AuctionListingController::class, 'bids'])->name('listings.bids.index');
    Route::post('/listings/{listing}/bids', [BidController::class, 'store'])->middleware('throttle:bids')->name('listings.bids.store');
    Route::get('/wallet', [WalletController::class, 'show'])->name('wallet.show');
    Route::post('/wallet/top-ups', [WalletTopUpController::class, 'store'])->name('wallet.top-ups.store');
});
