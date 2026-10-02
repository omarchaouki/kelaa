<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            // Null once the author deleted their account (review is anonymized).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->string('locale', 5)->nullable();
            $table->string('status', 30)->default('pending');
            // Reasons the abuse detector sent this review to moderation.
            $table->jsonb('flags')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('device_hash', 64)->nullable()->index();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderation_reason')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
            $table->index(['business_id', 'status', 'published_at']);
        });

        Schema::create('review_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('review_replies', function (Blueprint $table) {
            $table->id();
            // One reply per review.
            $table->foreignId('review_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->string('status', 30)->default('published');
            $table->timestamp('edited_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['review_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_votes');
        Schema::dropIfExists('review_replies');
        Schema::dropIfExists('review_revisions');
        Schema::dropIfExists('reviews');
    }
};
