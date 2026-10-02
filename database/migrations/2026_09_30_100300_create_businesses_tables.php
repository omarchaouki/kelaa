<?php

use App\Support\Geo\SpatialSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('neighborhood_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->jsonb('description')->nullable();
            $table->string('address')->nullable();
            SpatialSchema::point($table);
            $table->string('phone', 20)->nullable()->index();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            // {"facebook": "...", "instagram": "...", "tiktok": "..."}
            $table->jsonb('social_links')->nullable();
            $table->unsignedTinyInteger('price_range')->nullable();
            $table->boolean('is_always_open')->default(false);
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_claimed')->default(false);
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('plan_expires_at')->nullable();
            // Legal info used on invoices.
            $table->string('legal_name')->nullable();
            $table->string('ice', 20)->nullable();
            $table->string('legal_address')->nullable();
            $table->unsignedTinyInteger('profile_completeness')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('bayesian_score', 5, 4)->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['city_id', 'slug']);
            $table->index(['city_id', 'status', 'bayesian_score']);
        });

        Schema::create('business_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);

            $table->unique(['business_id', 'category_id']);
            $table->index(['category_id', 'is_primary']);
        });

        // Several rows per day allow split hours (09:00-13:00 + 15:00-20:00).
        // A closing time earlier than the opening time runs past midnight.
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            // ISO-8601: 1 = Monday ... 7 = Sunday
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);

            $table->index(['business_id', 'day_of_week']);
        });

        // Exceptions (Ramadan, Eid, public holidays). They replace the regular
        // hours for every date between starts_on and ends_on.
        Schema::create('business_special_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->jsonb('label')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->index(['business_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_special_hours');
        Schema::dropIfExists('business_hours');
        Schema::dropIfExists('business_category');
        Schema::dropIfExists('businesses');
    }
};
