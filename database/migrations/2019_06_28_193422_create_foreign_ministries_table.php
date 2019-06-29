<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateForeignMinistriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('foreign_ministries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('website')->nullable();
            $table->text('name')->nullable();
            $table->text('minister')->nullable();
            $table->text('headquarters')->nullable();
            $table->text('wikipage_url');
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
        Schema::dropIfExists('foreign_ministries');
    }
}
