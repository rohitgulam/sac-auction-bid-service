<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_top_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key', 128);
            $table->unsignedBigInteger('amount_minor');
            $table->jsonb('response_body')->nullable();
            $table->timestamps();

            $table->unique(['wallet_id', 'idempotency_key']);
            $table->index(['wallet_id', 'created_at']);
        });

        DB::statement('ALTER TABLE wallet_top_ups ADD CONSTRAINT wallet_top_ups_fixed_amount CHECK (amount_minor IN (1000000, 5000000))');
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_top_ups');
    }
};
