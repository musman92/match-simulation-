<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInningsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('innings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('inning')->default(1);
            $table->unsignedBigInteger('tournament_id');
            $table->unsignedBigInteger('match_id');
            $table->unsignedBigInteger('batting_team_id');
            $table->unsignedBigInteger('bowling_team_id');
            $table->unsignedBigInteger('batsman_id');
            $table->unsignedBigInteger('bowler_id');

            $table->unsignedBigInteger('other_batsman_id');

            $table->float('ball');

            $table->string('status');
            $table->string('status2')->nullable();
            $table->integer('result');

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
        Schema::dropIfExists('innings');
    }
}
