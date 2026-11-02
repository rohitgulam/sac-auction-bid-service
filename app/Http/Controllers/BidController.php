<?php

namespace App\Http\Controllers;

use App\Actions\PlaceBid;
use App\Http\Requests\PlaceBidRequest;
use App\Models\AuctionListing;
use Illuminate\Http\JsonResponse;

class BidController extends Controller
{
    public function store(PlaceBidRequest $request, AuctionListing $listing, PlaceBid $placeBid): JsonResponse
    {
        $validated = $request->validated();
        $result = $placeBid->handle(
            $request->demoDealer,
            $listing,
            (int) $validated['amount_aed'] * 100,
            $validated['idempotency_key'],
        );

        return response()->json($result->body, $result->status);
    }
}
