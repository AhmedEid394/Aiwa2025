<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeideaPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('geidea_payments', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->decimal('service_amount', 10, 2);
            $table->decimal('bm_fees', 10, 2)->nullable();
            $table->decimal('geidea_fees', 10, 2)->nullable();
            $table->decimal('aiwa_fees', 10, 2)->nullable();
            $table->decimal('tax_14_percent', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->boolean('cash_in')->default(false);
            $table->boolean('cash_out')->nullable();
            $table->string('currency')->default('EGP');
            $table->string('status')->nullable();
            $table->unsignedBigInteger('reservation_id');
            $table->unsignedBigInteger('user_send_id');
            $table->unsignedBigInteger('user_receive_id');
            $table->string('payment_intent_id')->nullable();
            $table->string('merchant_reference_id')->nullable();
            $table->timestamps();

            $table->foreign('reservation_id')->references('booking_id')->on('bookings')->onDelete('cascade');
            $table->foreign('user_send_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('user_receive_id')->references('provider_id')->on('service_providers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('geidea_payments');
    }
}