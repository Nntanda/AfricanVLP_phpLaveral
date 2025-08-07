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
        Schema::create('resource_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name');
            $table->string('file_path')->nullable(); // For local storage
            $table->string('cloudinary_public_id')->nullable(); // For Cloudinary
            $table->enum('storage_type', ['local', 'cloudinary'])->default('local');
            $table->string('mime_type');
            $table->string('file_extension');
            $table->bigInteger('file_size'); // in bytes
            $table->integer('download_count')->default(0);
            $table->json('metadata')->nullable(); // For storing additional file metadata
            $table->boolean('is_primary')->default(false); // Mark primary file for resource
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['resource_id', 'is_active']);
            $table->index(['storage_type', 'is_active']);
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_files');
    }
};