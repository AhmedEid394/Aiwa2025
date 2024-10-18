<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class CreatePapersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('papers', function (Blueprint $table) {
          $table->id('paper_id'); // Auto-incrementing ID
          $table->unsignedBigInteger('provider_id')->nullable(); // Foreign key for service provider
          $table->foreign('provider_id')->references('provider_id')->on('service_providers')->onDelete('cascade');
          $table->unsignedBigInteger('user_id')->nullable(); 
          $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
          $table->string('front_photo')->nullable(); 
          $table->string('back_photo')->nullable(); 
          $table->boolean('is_verified')->default(false);
          $table->text('notes')->nullable(); 
          $table->timestamps(); 
      });
  }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('papers');
    }
};
