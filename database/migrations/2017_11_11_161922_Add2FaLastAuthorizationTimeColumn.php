<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Add2FaLastAuthorizationTimeColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('totp_secret')->nullable()->change();
            $table->timestampTz('totp_authenticated_at')->after('totp_secret')->nullable();
        });

        // Using Eloquent to interact with the data
        User::whereNotNull('totp_secret')->get()->each(function ($user) {
            $user->totp_secret = encrypt($user->totp_secret);
            $user->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Using Eloquent to interact with the data
        User::whereNotNull('totp_secret')->get()->each(function ($user) {
            $user->totp_secret = decrypt($user->totp_secret);
            $user->save();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->char('totp_secret', 16)->nullable(false)->change();
            $table->dropColumn('totp_authenticated_at');
        });
    }
}
