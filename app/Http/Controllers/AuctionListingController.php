<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuctionListingResource;
use App\Http\Resources\BidResource;
use App\Models\AuctionListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuctionListingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'kind' => 'sometimes|in:vehicle,plate',
            'offer_type' => 'sometimes|in:live,deal',
            'status' => 'sometimes|in:open,closed',
        ]);

        $listings = AuctionListing::query()
            ->with(['bids.dealer'])
            ->when(isset($filters['kind']), fn ($query) => $query->where('kind', $filters['kind']))
            ->when(isset($filters['offer_type']), fn ($query) => $query->where('offer_type', $filters['offer_type']))
            ->when(($filters['status'] ?? null) === 'open', fn ($query) => $query->open())
            ->when(($filters['status'] ?? null) === 'closed', fn ($query) => $query->closed())
            ->orderByDesc('ends_at')
            ->get();

        return AuctionListingResource::collection($listings);
    }

    public function show(AuctionListing $listing): AuctionListingResource
    {
        return new AuctionListingResource($listing->load('bids.dealer'));
    }

    public function bids(AuctionListing $listing): AnonymousResourceCollection
    {
        return BidResource::collection($listing->bids()->with('dealer')->orderByDesc('amount_minor')->get());
    }

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'listing_count' => AuctionListing::query()->count(),
            'vehicle_count' => AuctionListing::query()->where('kind', 'vehicle')->count(),
            'plate_count' => AuctionListing::query()->where('kind', 'plate')->count(),
            'selected_bidder_code' => $request->session()->get('demo_dealer_code'),
        ]);
    }
}
