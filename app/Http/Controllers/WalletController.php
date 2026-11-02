<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $dealer = Dealer::query()->with('wallet')->find($request->session()->get('demo_dealer_id'));

        if (! $dealer?->wallet) {
            return response()->json([
                'error' => ['code' => 'demo_dealer_required', 'message' => 'Select a demo dealer to view a wallet.'],
            ], 401);
        }

        return response()->json([
            'bidder_code' => $dealer->code,
            ...$dealer->wallet->summary(),
            'activity' => $dealer->wallet->topUpActivity(),
        ]);
    }
}
