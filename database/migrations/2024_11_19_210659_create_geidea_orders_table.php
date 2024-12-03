<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeideaOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('geidea_orders', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();
            $table->string('order_id')->unique();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency');
            $table->string('language')->nullable();
            $table->string('detailed_status')->nullable();
            $table->string('status')->nullable();
            $table->string('three_d_secure_id')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('merchant_public_key')->nullable();
            $table->string('merchant_reference_id')->nullable();
            $table->string('order_source')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->dateTime('updated_date')->nullable();
            $table->string('response_code')->nullable();
            $table->string('detailed_response_code')->nullable();
            $table->string('payment_method_type')->nullable();
            $table->string('payment_method_brand')->nullable();
            $table->string('payment_method_cardholder_name')->nullable();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->timestamps();

            $table->foreign('reservation_id')->references('booking_id')->on('bookings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('geidea_orders');
    }
}