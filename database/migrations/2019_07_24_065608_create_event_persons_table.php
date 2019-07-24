<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventPersonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_persons', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->unsignedInteger('person_id');
            $table->timestamps();

            $table->foreign('event_id')
                ->references('id')
                ->on('event');

            $table->foreign('person_id')
                ->references('id')
                ->on('person');

            $table->unique(["event_id", "person_id"], "uk_event_id_person_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_persons');
    }
}
