<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id('chat_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('user_type', ['user', 'Provider']); // User or service provider
            $table->unsignedBigInteger('provider_id');
            $table->foreign('provider_id')->references('provider_id')->on('service_providers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
