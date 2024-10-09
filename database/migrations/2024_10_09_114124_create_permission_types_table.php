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
        $table->id('permission_type_id'); // Primary key
        $table->string('name')->unique(); // Name of the permission type (e.g., 'access_location', 'access_files')
        $table->string('description')->nullable(); // Description of the permission type
        $table->timestamps(); // Created and updated timestamps
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
