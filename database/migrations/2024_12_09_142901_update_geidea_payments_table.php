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
        Schema::table('geidea_payments', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['reservation_id']);
            $table->dropForeign(['user_send_id']);

            // Add user_type column
            $table->enum('user_send_type', ['user', 'provider'])->default('user')->after('user_send_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('geidea_payments', function (Blueprint $table) {
            // Recreate foreign keys
            $table->foreign('reservation_id')->references('booking_id')->on('bookings')->onDelete('cascade');
            $table->foreign('user_send_id')->references('user_id')->on('users')->onDelete('cascade');

            // Drop user_type column
            $table->dropColumn('user_send_type');
        });
    }
};
