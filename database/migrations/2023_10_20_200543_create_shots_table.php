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
        Schema::create('shots', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Group::class);
            $table->string("user_chat_id");
            $table->json('pages');
            $table->string('card_number');
            $table->string('shaba_number');
            $table->string('card_name');
            $table->bigInteger('amount')->default(0);
            $table->integer('subtraction')->default(0);
            $table->float('fee')->default(0);

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
        Schema::dropIfExists('shots');
    }
};
