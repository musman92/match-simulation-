<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Tournament;
use App\Inning;
use App\Team;
use App\Match;
use App\Player;


class everyFiveSec extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'match:score';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'making match scores.....';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $tours = Tournament::where('started', 1)->get();
        foreach ($tours as $tour) {
            $matches = Match::where('tournament_id', $tour->id)
                ->where('status', '!=', 2)
                ->get();

            // dd($matches);
            // get matches
            foreach ($matches as $match) {
                // dd($match);
                $last = Inning::where('tournament_id', $tour->id)
                    ->where('match_id', $match->id)
                    ->orderBy('id', 'desc')->first();
                // dd($last);
                // we are doing one over in one cycle
                if( !isset($last->id) ) {
                    $match->status = 1;
                    $match->save();
                    $innings = 1;

                    // start match
                    $toss = rand(0, 1);
                    $batting_team_id = $toss == 0 ? $match->team1 : $match->team2;
                    $bowling_team_id = $toss == 1 ? $match->team1 : $match->team2;

                    $batting_team = Team::find($batting_team_id);
                    $bowling_team = Team::find($bowling_team_id);

                    // picking batsman as we have in db
                    $batsman = DB::table('team_players as tp')
                        ->join('players as p', 'p.id', '=', 'tp.player_id')
                        ->where('tp.team_id', $batting_team_id)
                        ->where('p.type', 'BT')
                        ->limit(2)
                        ->get();

                    $batsman1 = $batsman[0];
                    $batsman2 = $batsman[1];

                    //picking the bowler
                    $bowler = DB::table('team_players as tp')
                        ->join('players as p', 'p.id', '=', 'tp.player_id')
                        ->where('tp.team_id', $bowling_team_id)
                        ->where('p.type', 'BT')
                        ->first();

                    $this->runAnOver($tour->id, $match->id, $innings, null, $batting_team_id, $bowling_team_id, $batsman1, $batsman2, $bowler, 0);
                } else {
                    // next over
                    // now we need to pick the next bowler and the bowler who face
                    $innings = $last->inning;
                    $next_over = $last->ball;
                    // get batsman team
                    $rst = DB::table('team_players as tp')
                        ->select('tp.id', 'team_id')
                        ->join('players as p', 'p.id', '=', 'tp.player_id')
                        ->where('tp.player_id', $last->other_batsman_id)
                        ->first();
                    $batting_team_id = $rst->team_id;

                    //get bowlers team
                    $rst = DB::table('team_players as tp')
                        ->select('tp.id', 'team_id')
                        ->join('players as p', 'p.id', '=', 'tp.player_id')
                        ->where('tp.player_id', $last->bowler_id)
                        ->first();
                    $bowling_team_id = $rst->team_id;

                    $change_inings_flag = false;

                    //checking if all team out
                    $b2 = $this->getNextPlayer($tour->id, $match->id, $batting_team_id);

                    // dd($b2);
                    // check overs
                    if($last->ball == 20 || $b2 == false) {
                        if($last->inning == 2 ) {
                            // match ends
                            $match->status = 2;
                            $match->save();
                            break;
                            return;
                        }
                        // change inings
                        $a = $bowling_team_id;
                        $bowling_team_id = $batting_team_id;
                        $batting_team_id = $a;

                        // picking batsman as we have in db
                        $batsman = DB::table('team_players as tp')
                            ->join('players as p', 'p.id', '=', 'tp.player_id')
                            ->where('tp.team_id', $batting_team_id)
                            ->where('p.type', 'BT')
                            ->limit(2)
                            ->get();

                        $batsman1 = $batsman[0];
                        $batsman2 = $batsman[1];
                        $change_inings_flag = true;
                        $innings = 2;
                        $next_over = 0;
                    } else {
                        // continue
                        //check if player is out on last ball
                        if($last->status == 'out') {
                            // pick next player
                            $batsman1 = Player::find($last->other_batsman_id);
                            $batsman2 = Player::find($b2);
                        } else {
                            if($last->result%2 == 0) {
                                $batsman1 = Player::find($last->other_batsman_id);
                                $batsman2 = Player::find($last->batsman_id);
                            } else {
                                $batsman1 = Player::find($last->batsman_id);
                                $batsman2 = Player::find($last->other_batsman_id);
                            }
                        }
                    }

                    $qry = DB::table('team_players as tp')
                        ->join('players as p', 'p.id', '=', 'tp.player_id')
                        ->where('tp.team_id', $bowling_team_id)
                        ->where('p.type', '!=', 'BT');
                    if($change_inings_flag == false) {
                        $qry->where('p.id', '!=', $last->bowler_id);
                    }
                    $bowler_opts = $qry->get();
                    $bowler_opts = $bowler_opts->toArray();

                    $idx = array_rand($bowler_opts);
                    $bowler = $bowler_opts[$idx];

                    $this->runAnOver($tour->id, $match->id, $innings, $last, $batting_team_id, $bowling_team_id, $batsman1, $batsman2, $bowler, $next_over);
                }
            }
        }
    }

    function get_random_result() {
        $opts = [
            0, 0, 0, 0, 0,
            1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
            2, 2, 2, 2, 2, 2,
            3, 3, 3, 3,
            4, 4, 4,
            6, 6,
            'bowled',
            'catch',
        ];

        // get random index from array $arrX
        $randIndex = array_rand($opts);

        // output the value for the random index
        return $opts[$randIndex];
    }

    function get_random_ball() {
        $opts = [
            'wide',
            'noball',
            'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok'
        ];

        // get random index from array $arrX
        $randIndex = array_rand($opts);

        // output the value for the random index
        return $opts[$randIndex];
    }

    function runAnOver ($tour_id, $match_id, $innings, $last, $batting_team_id, $bowling_team_id, $batsman1, $batsman2, $bowler, $over) {
        // dd($innings);
        //serve over
        $ball_count = 0;
        $completed_over = $over;
        $over += 0.1;
        $facing_player_id = $batsman1->id;
        $other_batsman_id = $batsman2->id;

        $bowler_id = $bowler->id;
        $stop_flag = false;
        $current_over_score = 0;

        // if innings == 2
        // we need to check if score is completed or not
        if($innings == 2) {
            $score_to_chase = Inning::where('tournament_id', $tour_id)
                ->where('match_id', $match_id)
                ->where('inning', 1)
                ->sum('result');
            $current_total_score = Inning::where('tournament_id', $tour_id)
                ->where('match_id', $match_id)
                ->where('inning', 2)
                ->sum('result');
            // echo $current_total_score;
            // dd($score_to_chase);
        }

        // all batting team
        $batting_team = DB::table('team_players as tp')
                        ->select('p.*')
                        ->join('players as p', 'p.id', '=', 'tp.player_id')
                        ->where('tp.team_id', $batting_team_id)
                        ->get();
        $batting_player_ids = [];
        foreach ($batting_team as $pl) {
            $batting_team_ids[] = $pl->id;
        }
        // dd(1);
        // get out player
        $player_outs = DB::table('innings as i')
                    ->select('p.*')
                    ->join('players as p', 'p.id', '=', 'i.batsman_id')
                    ->where('i.tournament_id', $tour_id)
                    ->where('i.match_id', $match_id)
                    ->where('i.status', 'out')
                    ->whereIn('batsman_id', $batting_team_ids)
                    ->get();
        $player_out_ids = [];
        foreach ($player_outs as $pl) {
            $player_out_ids[] = $pl->id;
        }

        // dd(1);
        $player_will_bat = array_diff($batting_team_ids, $player_out_ids);
        while($ball_count < 6) {
            echo $ball_count.' <br/>';
            $ball = $this->get_random_ball();
            // to insert
            $to_insert = [
                'inning' => $innings,
                'tournament_id' => $tour_id,
                'match_id' => $match_id,
                'batsman_id' => $facing_player_id,
                'other_batsman_id' => $other_batsman_id,
                'bowler_id' => $bowler_id,
                'ball' => $over,
            ];

            if($ball == 'wide') {
                $to_insert['status'] = 'wide';
                $to_insert['result'] = 1;
            } else {
                $ball_count++;
                $rst = $this->get_random_result();
                $out = false;
                if($rst == 'bowled') {
                    // echo "bow";
                    // dd($bowler->name);
                    $to_insert['status2'] = 'b '.$bowler->name;
                    $out = true;
                } else if($rst == 'catch') {
                    $catchers = DB::table('team_players as tp')
                        ->join('players as p', 'p.id', '=', 'tp.player_id')
                        ->where('tp.team_id', $bowling_team_id)
                        ->get()->random(1);
                    $catcher = $catchers[0];
                    //     echo "cat";
                    // dd($catcher);
                        $text = 'c '.$catcher->name.' b '.$bowler->name;
                    if($catcher->id == $bowler_id) {
                        $text = 'c & b '.$bowler->name;
                    }
                    $to_insert['status2'] = $text;
                    $out = true;
                }
                if($out) {
                    // out and change player
                    $to_insert['result'] = 0;
                    $to_insert['status'] = 'out';

                    // adding him to out player list
                    array_push($player_out_ids, $facing_player_id);

                    // updating palyer list to who will play
                    $player_will_bat = array_diff($batting_team_ids, $player_out_ids);
                    // dd($player_will_bat);

                    if(sizeof($player_out_ids) == 10) {
                        $stop_flag =  true;
                    } else {
                        //getting a randome player
                        $randIndex = array_rand($player_will_bat);

                        //assign him
                        $facing_player_id = $player_will_bat[$randIndex];
                    }
                } else {
                    $to_insert['status'] = 'runs';
                    $to_insert['result'] = $rst;
                    if($rst%2 == 1) {
                        $a = $other_batsman_id;
                        $other_batsman_id = $facing_player_id;
                        $facing_player_id = $a;
                    }
                }
            }
            $current_over_score += $to_insert['result'];
            if($ball_count == 6) {
                $to_insert['ball'] = $completed_over+1;
            }
            $in = Inning::create($to_insert);
            if($innings == 2 && $score_to_chase < $current_total_score+$current_over_score) {
                // team 2 wins by chasing
                $match = Match::find($match_id);
                $match->status = 2;
                if($stop_flag) {
                    // bowling team wins
                    $match->winner_team = $bowling_team_id;
                } else {
                    $stop_flag = true;
                    // batting team wins
                    $match->winner_team = $batting_team_id;
                }
                $match->save();
            }
            if($stop_flag) {
                $ball_count = 6;
            }
            $over += 0.1;
        }
    }

    public function getNextPlayer($tour_id, $match_id, $batting_team_id) {
        $batting_team = DB::table('team_players as tp')
                        ->select('p.*')
                        ->join('players as p', 'p.id', '=', 'tp.player_id')
                        ->where('tp.team_id', $batting_team_id)
                        ->get();
        $batting_player_ids = [];
        foreach ($batting_team as $pl) {
            $batting_team_ids[] = $pl->id;
        }

        // get out player
        $player_outs = DB::table('innings as i')
                    ->select('p.*')
                    ->join('players as p', 'p.id', '=', 'i.batsman_id')
                    ->where('i.tournament_id', $tour_id)
                    ->where('i.match_id', $match_id)
                    ->where('i.status', 'out')
                    ->whereIn('batsman_id', $batting_team_ids)
                    ->get();
        $player_out_ids = [];
        foreach ($player_outs as $pl) {
            $player_out_ids[] = $pl->id;
        }

        if (sizeof($player_out_ids) == 10) {
            return false;
        }

        // updating palyer list to who will play
        $player_will_bat = array_diff($batting_team_ids, $player_out_ids);

        //getting a randome player
        $randIndex = array_rand($player_will_bat);

        //assign him
        return $player_will_bat[$randIndex];
    }
}
