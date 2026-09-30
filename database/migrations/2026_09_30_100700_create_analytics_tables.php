<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('source', 20);
            $table->foreignId('sponsored_placement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['business_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('business_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 30);
            $table->string('source', 20);
            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('unique_sessions')->default(0);

            $table->unique(['business_id', 'date', 'type', 'source'], 'stats_daily_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_stats_daily');
        Schema::dropIfExists('contact_events');
    }
};
