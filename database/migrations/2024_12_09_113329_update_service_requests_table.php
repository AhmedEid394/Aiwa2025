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
        Schema::table('service_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('provider_id')->after('user_id')->nullable();
            $table->enum('status', ['request', 'accepted but not payed','accepted', 'rejected', 'done'])->default('request')->change();
            $table->string('building_number')->after('location');
            $table->string('apartment')->after('building_number');
            $table->string('location_mark')->after('apartment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
