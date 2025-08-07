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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('title', 100)->nullable();
            $table->string('slug', 255)->nullable();
            $table->text('content')->nullable();
            $table->integer('status')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->datetime('created')->nullable();
            $table->datetime('modified')->nullable();
            
            $table->index('organization_id');
            $table->index('region_id');
            
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->foreign('region_id')->references('id')->on('regions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};