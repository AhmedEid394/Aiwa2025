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
        Schema::create('bm_cashout_status', function (Blueprint $table) {
            $table->id('bm_cashout_status_id');
            $table->unsignedBigInteger('bm_cashout_id');  
            $table->char('message_id', 100)->unique();
            $table->char('transaction_id', 100);
            $table->char('transaction_status_code', 6)->nullable();
            $table->string('transaction_status_description', 500)->nullable();
            $table->timestamps();

            $table->foreign('bm_cashout_id')
                ->references('bm_cashout_id')
                ->on('bm_cashout_prepare')
                ->onDelete('cascade');  

            $table->foreign('transaction_id')
                ->references('transaction_id')
                ->on('bm_cashout_prepare')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bm_cashout_status');
    }
};
