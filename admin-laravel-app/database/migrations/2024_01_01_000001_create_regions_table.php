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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45)->nullable();
            $table->string('continent', 45)->nullable();
            $table->timestamps();
            
            // Rename Laravel timestamps to CakePHP convention
            $table->renameColumn('created_at', 'created');
            $table->renameColumn('updated_at', 'modified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};