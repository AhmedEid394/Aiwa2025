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
      Schema::create('sub_categories', function (Blueprint $table) {
        $table->id('sub_category_id'); // Primary key
        $table->unsignedBigInteger('category_id'); // FK to categories
        $table->foreign('category_id')->references('category_id')->on('categories')->onDelete('cascade');// FK to categories
        $table->string('name'); // Subcategory name
        $table->longText('image')->nullable(); // Image for the subcategory
        $table->text('description')->nullable(); // Description of the subcategory
        $table->timestamps(); // Timestamps for created_at and updated_at
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_categories');
    }
};
