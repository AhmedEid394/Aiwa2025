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
      Schema::create('promo_codes', function (Blueprint $table) {
          $table->id('promo_code_id'); // Auto-incrementing ID
          $table->string('code', 50)->unique(); // Promo code
          $table->decimal('percentage', 5, 2); // Discount percentage
          $table->unsignedBigInteger('provider_id'); // FK to categories
          $table->foreign('provider_id')->references('provider_id')->on('service_providers')->onDelete('cascade');// FK to categories
          $table->date('due_date'); 
          $table->unsignedBigInteger('service_id'); // FK to categories
          $table->foreign('service_id')->references('service_id')->on('services')->onDelete('cascade');// FK to categories// The booked service
          $table->timestamps(); 

          // Foreign key constraints
      });
  }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
