<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger("location_id");
            $table->unsignedBigInteger("page_piece_id");
            $table->dateTime("happening_at")->default(null);
            $table->dateTime("published_at")->nullable();

            $table->foreign("location_id")
                ->references("id")
                ->on("location");
            
            $table->foreign("page_piece_id")
                ->references("id")
                ->on("page_piece");

            $table
                ->unique("page_piece_id", "uk_page_piece_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event');
    }
}
