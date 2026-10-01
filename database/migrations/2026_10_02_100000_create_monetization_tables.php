<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sponsored slot prices, edited in the admin. The most specific row wins:
        // (city, category) > (city, any category) > (any city, category) > (any, any).
        Schema::create('placement_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->decimal('price_week', 10, 2);
            $table->decimal('price_month', 10, 2);
            // Neighborhood-targeted slots usually cost less (percentage of the city-wide price).
            $table->unsignedTinyInteger('neighborhood_percent')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['city_id', 'category_id', 'position']);
        });

        Schema::table('payments', function (Blueprint $table) {
            // subscription | placement
            $table->string('purpose', 20)->default('subscription')->after('method');
            // Unpaid orders are cancelled (and their slot released) after this moment.
            $table->timestamp('expires_at')->nullable()->after('status');
        });

        Schema::table('sponsored_placements', function (Blueprint $table) {
            // week | month, and how many of them were bought.
            $table->string('billing_unit', 10)->default('week')->after('position');
            $table->unsignedSmallInteger('units')->default(1)->after('billing_unit');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->nullable()->after('billing_period');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', fn (Blueprint $table) => $table->dropColumn('amount'));
        Schema::table('sponsored_placements', fn (Blueprint $table) => $table->dropColumn(['billing_unit', 'units']));
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn(['purpose', 'expires_at']));
        Schema::dropIfExists('placement_rates');
    }
};
