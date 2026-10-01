<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moderation queues list pending reviews and replies oldest first; without
 * these indexes they scan the whole table as it grows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });

        Schema::table('review_replies', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('review_replies', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
