<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('address');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('timezone')->nullable()->after('longitude');
            $table->json('address_components')->nullable()->after('timezone');
            $table->string('place_id')->nullable()->after('address_components');
            
            // Add spatial index for geographic queries
            $table->index(['latitude', 'longitude'], 'organizations_coordinates_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex('organizations_coordinates_index');
            $table->dropColumn([
                'latitude', 
                'longitude', 
                'timezone', 
                'address_components', 
                'place_id'
            ]);
        });
    }
};