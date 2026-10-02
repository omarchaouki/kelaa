<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Listings a submission may duplicate (shown to moderators).
            $table->jsonb('possible_duplicate_ids')->nullable()->after('status');
        });

        Schema::table('edit_suggestions', function (Blueprint $table) {
            // Suggestions made by the verified owner are moderated first.
            $table->boolean('from_owner')->default(false)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('edit_suggestions', function (Blueprint $table) {
            $table->dropColumn('from_owner');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('possible_duplicate_ids');
        });
    }
};
