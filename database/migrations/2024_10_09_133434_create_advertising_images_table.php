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
      Schema::create('advertising_images', function (Blueprint $table) {
          $table->id('advertising_image_id'); // Primary key
          $table->string('image_path'); 
          $table->boolean('status')->default(1)->change();
          $table->timestamps(); 
      });
  }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertising_images');
    }
};
