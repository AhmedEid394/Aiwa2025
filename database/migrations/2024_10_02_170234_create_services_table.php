<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id('service_id');
            $table->string('title');
            $table->unsignedBigInteger('provider_id');
            $table->foreign('provider_id')->references('provider_id')->on('service_providers')->onDelete('cascade');
            $table->unsignedBigInteger('sub_category_id');
            $table->foreign('sub_category_id')->references('sub_category_id')->on('sub_categories')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('service_fee', 10, 2);
            $table->json('pictures')->nullable();
            $table->json('add_ons')->nullable();
            $table->decimal('sale_amount', 10, 2)->nullable();
            $table->decimal('sale_percentage', 5, 2)->nullable();
            $table->decimal('down_payment', 10, 2)->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('building');
            $table->string('apartment');
            $table->string('location_mark');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
}