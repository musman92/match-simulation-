<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tournament_id');

            $table->unsignedBigInteger('team1');
            $table->unsignedBigInteger('team2');

            $table->dateTime('start_at');
            $table->dateTime('end_at');

            $table->tinyInteger('status')->default(0)->comment('0 = started, 1 = started, 2 = ended');

            $table->unsignedBigInteger('winner_team')->nullable();
            $table->string('venue');


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
        Schema::dropIfExists('matches');
    }
}
