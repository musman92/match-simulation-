<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Un Guard model
        Model::unguard();

        $tour = factory(App\Tournament::class)->create(['name' => 'PSL']);

        $teams = [
            'Peshawar Zalmi',
            'Lahore Qalandars',
            'Karachi Kings',
            'Islamabad United',
            'Quetta Gladiators',
            'Multan Sultans',
        ];
        foreach ($teams as $name) {
            // creating teams
            $team = factory(App\Team::class, 1)->create(['name' => $name]);

            //creating teams
            $players = factory(App\Player::class, 6)->create(['type' => 'BT']);
            $players[] = factory(App\Player::class, 1)->create(['type' => 'AL']);
            $players[] = factory(App\Player::class, 1)->create(['type' => 'WK']);
            $players[] = factory(App\Player::class, 3)->create(['type' => 'BWL']);
            $to_insert = [];
            foreach ($players as $key => $player) {
                $team[0]->players()->attach($player, ['created_at' => date('Y-m-d H:i:s')]);
            }
        }

        // create matches
        $teams = DB::table('teams')->get();
        foreach ($teams as $team) {
            $collection = collect($teams);
            $list = $collection->shuffle();

            foreach ($list as $lis) {
                if($lis->id != $team->id) {
                    $tour = factory(App\Match::class)->create([
                        'tournament_id' => 1,
                            'team1' => $team->id,
                            'team2' => $lis->id,
                            'start_at' => date('Y-m-d H:i:s'),
                            'end_at' => date("Y-m-d H:i:s", strtotime('+3 hours')),
                    ]);
                }
            }
        }
        // team list

    }
}
