<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id'); // Primary key
            $table->unsignedBigInteger('user_id');
            $table->enum('user_type', ['user', 'Provider']); // User or service provider
            $table->enum('transaction_type', ['cash_out','cash_in']); // Transaction type
            $table->unsignedBigInteger('service_id')->nullable(); // FK to categories
            $table->foreign('service_id')->references('service_id')->on('services')->onDelete('cascade');// FK to categories// The booked service

            $table->unsignedBigInteger('booking_id')->nullable(); // FK to service requests (optional)
            $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Transaction amount
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending'); // Transaction status
            $table->string('transaction_reference')->nullable(); // Reference ID from the payment gateway

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
