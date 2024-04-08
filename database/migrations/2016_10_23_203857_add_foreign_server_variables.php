<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignServerVariables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('server_variables', function (Blueprint $table) {
            $table->unsignedInteger('server_id')->change();
            $table->unsignedInteger('variable_id')->change();
            $table->foreign('server_id')->references('id')->on('servers');
            $table->foreign('variable_id')->references('id')->on('service_variables');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('server_variables', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
            $table->dropForeign(['variable_id']);

            $table->mediumInteger('server_id')->change();
            $table->mediumInteger('variable_id')->change();
        });
    }
}
