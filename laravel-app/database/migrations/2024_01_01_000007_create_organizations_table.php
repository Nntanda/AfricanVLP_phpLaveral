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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 45)->nullable();
            $table->string('name', 100)->nullable();
            $table->text('about')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('logo', 255)->nullable();
            $table->unsignedBigInteger('institution_type_id')->nullable();
            $table->string('government_affliliation', 100)->nullable();
            $table->unsignedBigInteger('category_id')->nullable()->comment('category of organization');
            $table->date('date_of_establishment')->nullable();
            $table->string('phone_number', 16)->nullable();
            $table->string('website', 55)->nullable();
            $table->string('facebbok_url', 255)->nullable();
            $table->string('instagram_url', 255)->nullable();
            $table->string('twitter_url', 255)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('status')->nullable();
            $table->datetime('created')->nullable();
            $table->datetime('modified')->nullable();
            
            $table->index('category_id');
            $table->index('city_id');
            $table->index('country_id');
            $table->index('institution_type_id');
            
            $table->foreign('category_id')->references('id')->on('category_of_organizations');
            $table->foreign('city_id')->references('id')->on('cities');
            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('institution_type_id')->references('id')->on('institution_types');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};