<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->string('user_type');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('service_id');
            $table->foreign('service_id')->references('service_id')->on('services')->onDelete('cascade');
            $table->json('add_ons')->nullable();
            $table->string('building_number');
            $table->string('apartment');
            $table->string('location_mark');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->date('booking_date');
            $table->time('booking_time');
            $table->decimal('service_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('promo_code')->nullable();
            $table->enum('status', ['request', 'accepted', 'rejected', 'done'])->default('request');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
}