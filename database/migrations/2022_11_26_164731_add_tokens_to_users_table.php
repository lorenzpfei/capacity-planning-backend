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
        Schema::table('users', static function (Blueprint $table) {
            $table->string('task_user_id');
            $table->string('task_token');
            $table->string('task_refresh_token');
            $table->string('tracking_user_id');
            $table->string('tracking_token');
            $table->string('tracking_refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn('task_user_id');
            $table->dropColumn('task_token');
            $table->dropColumn('task_refresh_token');
            $table->dropColumn('tracking_user_id');
            $table->dropColumn('tracking_token');
            $table->dropColumn('tracking_refresh_token');
        });
    }
};
