<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('daemonSFTP');
            $table->longText('host')->after('daemonBase');
            $table->text('bearer_token')->after('host');
            $table->boolean('insecure')->default(false)->after('bearer_token');
            $table->string('service_type')->after('insecure');
            $table->string('storage_class')->after('service_type');
            $table->string('ns')->after('storage_class');
        });

        // /** @var \Illuminate\Contracts\Encryption\Encrypter $encrypter */
        $encrypter = Container::getInstance()->make(Encrypter::class);

        foreach (DB::select('SELECT bearer_token FROM nodes') as $datum) {
            DB::update('UPDATE nodes SET bearer_token = ? WHERE id = ?', [
                Uuid::uuid4()->toString(),
                $datum->bearer_token,
                $encrypter->encrypt($datum->bearer_token),
                $datum->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('host');
            $table->dropColumn('bearer_token');
            $table->dropColumn('insecure');
            $table->dropColumn('service_type');
            $table->dropColumn('storage_class');
            $table->dropColumn('ns');
        });
    }
};