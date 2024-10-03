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
      Schema::create('bookings', function (Blueprint $table) {
        $table->id('booking_id'); // Auto-incrementing booking ID
        $table->unsignedBigInteger('user_id'); // FK to categories
        $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');// FK to categories  // The user who made the booking
        $table->unsignedBigInteger('service_id'); // FK to categories
        $table->foreign('service_id')->references('service_id')->on('services')->onDelete('cascade');// FK to categories// The booked service
        $table->json('add_ons')->nullable(); // Array of selected add-ons with cost
        $table->string('location'); // Location (e.g., address)
        $table->string('building_number'); // Building number
        $table->string('apartment'); // Apartment number
        $table->string('location_mark')->nullable(); // A nearby landmark for clarity
        $table->date('booking_date'); // Date of the service
        $table->time('booking_time'); // Time of the service
        $table->decimal('service_price', 10, 2); // Price of the service
        $table->decimal('total_price', 10, 2); // Total price including add-ons
        $table->string('promo_code')->nullable(); // Promo code if applied
        $table->enum('status', ['request', 'accepted', 'rejected','done'])->default('request'); // Reservation status, can be 'pending', 'confirmed', etc.
        $table->timestamps(); // Timestamps for created_at and updated_at
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
