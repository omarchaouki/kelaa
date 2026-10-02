<?php

use App\Support\Geo\SpatialSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->jsonb('name');
            $table->string('slug')->unique();
            $table->decimal('center_latitude', 10, 7);
            $table->decimal('center_longitude', 10, 7);
            $table->unsignedTinyInteger('default_zoom')->default(13);
            $table->string('timezone')->default('Africa/Casablanca');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('neighborhoods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->jsonb('name');
            $table->string('slug');
            SpatialSchema::point($table, 'center', required: false);
            // GeoJSON polygon, optional. Kept as JSON until we need spatial queries on it.
            $table->jsonb('boundary')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['city_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neighborhoods');
        Schema::dropIfExists('cities');
    }
};
