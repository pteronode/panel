<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignAllocations extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->unsignedInteger('assigned_to')->change();
            $table->unsignedInteger('node')->change();
            $table->foreign('assigned_to')->references('id')->on('servers');
            $table->foreign('node')->references('id')->on('nodes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign('allocations_assigned_to_foreign');
            $table->dropForeign('allocations_node_foreign');

            $table->dropIndex('allocations_assigned_to_foreign');
            $table->dropIndex('allocations_node_foreign');

            $table->mediumInteger('assigned_to')->change();
            $table->mediumInteger('node')->change();
        });
    }
}
