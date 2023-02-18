<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clusters', function (Blueprint $table) {
            $table->string('sftp_port')->before('daemonSFTP');
            $table->renameColumn('daemonSFTP', 'sftp_image');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->renameColumn('r_nodes', 'r_clusters');
        });

        Schema::table('database_hosts', function (Blueprint $table) {
            $table->renameColumn('node_id', 'cluster_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clusters', function (Blueprint $table) {
            //
        });
    }
};
