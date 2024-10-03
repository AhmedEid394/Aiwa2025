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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id'); // Use auto-incrementing ID
            $table->unsignedBigInteger('user_id'); // Ensure this matches the type in the users table
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade'); // Foreign key to users table
            $table->string('title'); // Title of the notification
            $table->text('message'); // Notification message
            $table->longText('image')->nullable(); // Image associated with the notification (can store base64 or URL)
            $table->enum('type', ['offer_Request', 'offers', 'update_status', 'system'])->default('system'); // Type of notification
            $table->boolean('is_read')->default(false); // Status of the notification (whether it's read or unread)
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};