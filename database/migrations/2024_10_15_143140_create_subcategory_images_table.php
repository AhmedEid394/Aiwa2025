<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubcategoryImagesTable extends Migration
{
    public function up()
    {
        Schema::create('subcategory_images', function (Blueprint $table) {
            $table->id('subcategory_image_id');
            $table->unsignedBigInteger('sub_category_id'); 
            $table->foreign('sub_category_id')->references('sub_category_id')->on('sub_categories')->onDelete('cascade');
            $table->longText('image_path')->nullable(); 
            $table->string('alt_text')->nullable(); 
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('subcategory_images');
    }
}

