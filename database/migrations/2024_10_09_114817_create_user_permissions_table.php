<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class CreateUserPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    
          Schema::create('user_permissions', function (Blueprint $table) {
              $table->id('user_permission_id'); // Primary key
              $table->enum('user_type', ['user', 'provider']);
              $table->unsignedBigInteger('user_id');
              $table->unsignedBigInteger('permission_type_id'); 
              $table->foreign('permission_type_id')->references('permission_type_id')->on('permission_types')->onDelete('cascade');
              $table->boolean('is_allowed')->default(false); 
              $table->timestamps();
          });
      
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
