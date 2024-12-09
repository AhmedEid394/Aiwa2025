<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class CreatePapersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('papers', function (Blueprint $table) {
          $table->id('paper_id'); // Auto-incrementing ID
          $table->string('user_type');
          $table->unsignedBigInteger('user_id');
          $table->longText('front_photo')->nullable(); 
          $table->longText('back_photo')->nullable(); 
          $table->longText('criminal_record_photo')->nullable();
          $table->enum('status', ['pending','accepted', 'rejected'])->default('pending');
          $table->text('notes')->nullable(); 
          $table->timestamps(); 
      });
  }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('papers');
    }
};
