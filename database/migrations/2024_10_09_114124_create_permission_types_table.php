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
      Schema::create('permission_types', function (Blueprint $table) {
        $table->id('permission_type_id'); 
        $table->string('name')->unique(); 
        $table->string('description')->nullable(); 
        $table->timestamps(); 
    });

  }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_types');
    }
};
