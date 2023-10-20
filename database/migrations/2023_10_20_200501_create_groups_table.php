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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id');
            $table->string('name');
            $table->float('default_price')->default(0);
            $table->json('prices');
            $table->float('subtraction')->default(0);
            $table->boolean('show_name')->default(false);
            $table->integer('shot_count')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('total_subtraction')->default(0);
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
        Schema::dropIfExists('groups');
    }
};
