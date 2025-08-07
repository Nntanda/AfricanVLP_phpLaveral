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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->string('slug', 255)->nullable();
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('featured_image', 255)->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->integer('status')->nullable();
            $table->datetime('published_at')->nullable();
            $table->datetime('created')->nullable();
            $table->datetime('modified')->nullable();
            
            $table->index('author_id');
            $table->index('status');
            $table->index('published_at');
            
            $table->foreign('author_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};