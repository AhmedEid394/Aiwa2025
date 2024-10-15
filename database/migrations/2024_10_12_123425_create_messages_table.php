<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('chat_id');
            $table->foreign('chat_id')->references('chat_id')->on('chats')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->longText('file')->nullable();
            $table->enum('sender_type', ['user', 'provider']);
            $table->unsignedBigInteger('sender_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
