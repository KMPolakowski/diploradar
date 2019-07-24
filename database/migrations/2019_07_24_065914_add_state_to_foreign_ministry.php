<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStateToForeignMinistry extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('foreign_ministries', function (Blueprint $table) {
            $table->integer("state_id");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('foreign_ministries', function (Blueprint $table) {
            $table->dropColumn("state_id");
            $table->dropForeign("foreign_ministries_state_id_foreign");
        });
    }
}
