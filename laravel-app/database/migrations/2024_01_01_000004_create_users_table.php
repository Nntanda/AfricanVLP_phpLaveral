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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 45)->nullable();
            $table->string('last_name', 45)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('phone_number', 16)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('about')->nullable();
            $table->string('profile_picture', 255)->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('role', 45)->nullable();
            $table->integer('status')->nullable();
            $table->boolean('is_email_verified')->default(false);
            $table->string('email_verification_token', 255)->nullable();
            $table->string('password_reset_token', 255)->nullable();
            $table->datetime('password_reset_expires')->nullable();
            $table->integer('registration_status')->nullable();
            $table->datetime('created')->nullable();
            $table->datetime('modified')->nullable();
            
            $table->unique('email');
            $table->index('country_id');
            $table->index('city_id');
            
            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('city_id')->references('id')->on('cities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};