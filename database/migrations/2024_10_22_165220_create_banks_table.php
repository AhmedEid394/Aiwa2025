<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->text('bank_en');
            $table->text('bank_ar');
            $table->text('bank_short');
            $table->text('logo');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('banks');
    }
};
