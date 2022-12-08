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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assigned_user_id')->constrained('users');
            $table->string('name');
            $table->dateTime('completed')->nullable();
            $table->dateTime('due')->nullable();
            $table->dateTime('start')->nullable();
            $table->string('priority')->nullable();
            $table->text('custom_fields')->nullable();
            $table->integer('tracking_total')->nullable();
            $table->tinyText('tracking_users')->nullable();
            $table->integer('tracking_estimate')->nullable();
            $table->foreignId('tracking_highest_time_user_id')->nullable()->constrained('users');
            $table->foreignId('creator_user_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
