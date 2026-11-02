<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('deposit_minor');
            $table->timestamps();
        });

        Schema::create('auction_listings', function (Blueprint $table) {
            $table->id();
            $table->string('lot_number', 16)->unique();
            $table->string('kind', 16);
            $table->string('offer_type', 16);
            $table->string('title');
            $table->string('subtitle');
            $table->string('location', 80);
            $table->unsignedBigInteger('opening_amount_minor');
            $table->unsignedSmallInteger('vehicle_year')->nullable();
            $table->unsignedInteger('mileage_km')->nullable();
            $table->jsonb('inspection_summary')->nullable();
            $table->string('plate_number', 32)->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->timestamps();

            $table->index(['kind', 'offer_type', 'ends_at']);
        });

        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->timestamps();

            $table->index(['auction_listing_id', 'amount_minor']);
        });

        Schema::create('bid_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('auction_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->timestampTz('released_at')->nullable();
            $table->timestamps();

            $table->index(['dealer_id', 'released_at']);
            $table->index(['auction_listing_id', 'released_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_reservations');
        Schema::dropIfExists('bids');
        Schema::dropIfExists('auction_listings');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('dealers');
    }
};
