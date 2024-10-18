<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class CreateFavouritesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('favourites', function (Blueprint $table) {
            $table->id('favourite_id'); // Primary key
            $table->unsignedBigInteger('user_id'); // FK to categories
        $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');// FK to categories  // The user who made the booking
        $table->unsignedBigInteger('service_id'); // FK to categories
        $table->foreign('service_id')->references('service_id')->on('services')->onDelete('cascade');// FK to categories// The booked service
      $table->timestamps(); // Timestamps for when the favourite was created
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favourites');
    }
};
