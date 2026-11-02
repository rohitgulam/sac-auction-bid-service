<?php

namespace App\Http\Controllers;

use App\Actions\TopUpWallet;
use App\Http\Requests\TopUpWalletRequest;
use Illuminate\Http\JsonResponse;

class WalletTopUpController extends Controller
{
    public function store(TopUpWalletRequest $request, TopUpWallet $topUpWallet): JsonResponse
    {
        $validated = $request->validated();
        $result = $topUpWallet->handle(
            $request->demoDealer,
            (int) $validated['amount_aed'] * 100,
            $validated['idempotency_key'],
        );

        return response()->json($result->body, $result->status);
    }
}
