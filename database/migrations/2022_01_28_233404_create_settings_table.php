<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('admin_id');
            $table->string('brand_name');
            $table->string('brand_background_color');
            $table->string('brand_background_image');
            $table->string('brand_theme_color');
            $table->string('brand_logo');
            $table->string('message');
            $table->string('redeeming_point');
            $table->string('custom_message');
            $table->string('form_message');
            $table->integer('show_try_again');
            $table->string('try_again_text');
            $table->integer('limit_scan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
