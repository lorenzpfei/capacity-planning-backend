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
            $table->unsignedBigInteger('task_user_id')->nullable();
            $table->foreign('task_user_id')->references('task_user_id')->on('users');
            $table->string('name');
            $table->dateTime('completed')->nullable();
            $table->dateTime('due')->nullable();
            $table->dateTime('start')->nullable();
            $table->string('priority')->nullable();
            $table->text('custom_fields')->nullable();
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
