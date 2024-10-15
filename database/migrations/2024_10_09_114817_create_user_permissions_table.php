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
              $table->id(); // Primary key
              $table->unsignedBigInteger('user_id'); // FK to users
              $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
              $table->unsignedBigInteger('permission_type_id'); // FK to users
              $table->foreign('permission_type_id')->references('permission_type_id')->on('permission_types')->onDelete('cascade');
              $table->boolean('is_allowed')->default(false); // Whether the permission is granted
              $table->timestamps(); // Created and updated timestamps
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
