<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryImagesTable extends Migration
{
    public function up()
    {
        Schema::create('category_images', function (Blueprint $table) {
            $table->id('category_image_id');
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('category_id')->on('categories')->onDelete('cascade');
            $table->longText('image_path')->nullable();
            $table->string('alt_text')->nullable();
            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('category_images');
    }
}
