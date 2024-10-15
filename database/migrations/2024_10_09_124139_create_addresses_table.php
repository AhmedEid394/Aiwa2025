<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('addresses', function (Blueprint $table) {
          $table->id('address_id'); // Primary key
          $table->unsignedBigInteger('user_id')->nullable(); // FK to users
          $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
          $table->unsignedBigInteger('provider_id')->nullable(); // FK to service providers
          $table->foreign('provider_id')->references('provider_id')->on('service_providers')->onDelete('cascade');
          $table->string('city'); // City
          $table->string('postal_code'); 
          $table->string('street'); 
          $table->timestamps(); 
      });
  }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
