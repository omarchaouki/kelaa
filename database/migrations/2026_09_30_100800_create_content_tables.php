<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacies_de_garde', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->unique(['business_id', 'date']);
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            // cgu, privacy, review-guidelines, cookies, legal-notice, about, contact
            $table->string('slug')->unique();
            $table->jsonb('title');
            $table->jsonb('body');
            $table->jsonb('meta_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('search_synonyms', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            // ["سباك", "plombi"]
            $table->jsonb('synonyms');
            // Null means the synonym group applies to every locale.
            $table->string('locale', 5)->nullable();
            $table->timestamps();

            $table->unique(['term', 'locale']);
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('purpose', 30);
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'purpose', 'created_at']);
        });

        Schema::create('banned_words', function (Blueprint $table) {
            $table->id();
            $table->string('word')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_words');
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('search_synonyms');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('pharmacies_de_garde');
    }
};
