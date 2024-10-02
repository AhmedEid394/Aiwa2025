<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('f_name'); 
            $table->string('l_name'); 
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->unique();
            $table->enum('role', ['customer', 'provider'])->default('customer'); 
            $table->enum('gender', ['Male', 'Female']);
            $table->string('os'); 
            $table->date('birthday'); 
            $table->string('nationality'); 
            $table->string('profile_photo')->nullable();
            $table->string('address');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}