<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Row-locked counter so invoice numbers stay sequential per year under concurrency.
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence');
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            // Client legal info is copied at issue time: an invoice must never change afterwards.
            $table->string('client_name');
            $table->string('client_ice', 20)->nullable();
            $table->string('client_address')->nullable();
            // [{"description": "...", "quantity": 1, "unit_price": "199.00", "total": "199.00"}]
            $table->json('lines');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('MAD');
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['year', 'sequence']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('MAD');
            $table->string('method', 30);
            $table->string('reference')->nullable()->unique();
            // Receipt photo is stored with medialibrary, collection "receipt".
            $table->string('status', 20)->default('pending')->index();
            $table->json('gateway_payload')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('billing_period', 10);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reminded_7d_at')->nullable();
            $table->timestamp('reminded_1d_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });

        Schema::create('sponsored_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('neighborhood_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('position');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 20)->default('pending');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamp('reminded_7d_at')->nullable();
            $table->timestamp('reminded_1d_at')->nullable();
            $table->timestamps();

            $table->index(['city_id', 'category_id', 'neighborhood_id', 'status', 'starts_at', 'ends_at'], 'placements_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsored_placements');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_sequences');
    }
};
