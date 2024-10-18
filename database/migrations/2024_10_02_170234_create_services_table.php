<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('services', function (Blueprint $table) {
            $table->id('service_id'); // Auto-incrementing primary key
            $table->string('title'); // Title of the service
            $table->unsignedBigInteger('provider_id'); // FK to categories
            $table->foreign('provider_id')->references('provider_id')->on('service_providers')->onDelete('cascade');// FK to categories
            $table->unsignedBigInteger('sub_category_id'); // FK to categories
            $table->foreign('sub_category_id')->references('sub_category_id')->on('sub_categories')->onDelete('cascade');
            $table->text('description')->nullable(); // Description of the service
            $table->decimal('service_fee', 10, 2); // Base fee for the service
            $table->json('pictures')->nullable(); // Array of base64 encoded images
            $table->json('add_ons')->nullable(); // Array of add-ons (title and cost)
            $table->decimal('sale_amount', 10, 2)->nullable(); // Sale amount (if applicable)
            $table->decimal('sale_percentage', 5, 2)->nullable(); // Sale percentage (if applicable)
            $table->decimal('down_payment', 10, 2)->nullable(); // Down payment amount
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
