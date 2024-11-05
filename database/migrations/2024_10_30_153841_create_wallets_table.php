<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id('wallet_id');
            $table->unsignedBigInteger('provider_id');
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->decimal('available_amount', 10, 2)->default(0.00);
            $table->timestamps();
            $table->foreign('provider_id')->references('provider_id')->on('service_providers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallets');
    }
}
