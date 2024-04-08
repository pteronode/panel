<?php

use Kubectyl\Models\Server;
use Kubectyl\Models\Allocation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyIpStorageMethod extends Migration
{
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->mediumInteger('allocation')->unsigned()->after('oom_disabled');
        });

        $servers = Server::all();
        foreach ($servers as $server) {
            $allocation = Allocation::where('ip', $server->ip)
                ->where('port', $server->port)
                ->where('node', $server->node)
                ->first();

            if ($allocation) {
                $server->allocation = $allocation->id;
                $server->save();
            }
        }

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('port');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('ip');
        });
    }

    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('ip')->after('allocation');
            $table->integer('port')->unsigned()->after('ip');
        });

        $servers = Server::with('allocation')->get();

        foreach ($servers as $server) {
            if ($server->allocation) {
                $server->ip = $server->allocation->ip;
                $server->port = $server->allocation->port;
                $server->save();
            }
        }

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('allocation');
        });
    }
}
