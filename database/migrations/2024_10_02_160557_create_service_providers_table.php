<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceProvidersTable extends Migration
{
    public function up()
    {
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id('provider_id'); 
            $table->string('f_name');
            $table->string('l_name'); 
            $table->string('email')->unique(); 
            $table->string('phone')->unique(); 
            $table->enum('provider_type', ['freelance', 'corporate']); 
            $table->date('date_of_birth'); 
            $table->enum('nationality', ['Egyptian', 'Foreigner']);
            $table->enum('gender', ['Male', 'Female']);
            $table->longText('profile_photo')->nullable();
            $table->unsignedBigInteger('service_category_id')->nullable(); // FK to categories
            $table->foreign('service_category_id')->references('sub_category_id')->on('sub_categories')->onDelete('cascade');
            $table->string('tax_record')->nullable();
            $table->string('company_name')->nullable();
            $table->string('id_number')->nullable();
            $table->string('passport_number')->nullable(); 
            $table->string('password'); 
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_providers');
    }

}
