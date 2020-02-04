<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Tournament;
use App\Inning;
use App\Team;
use App\Match;
use App\Player;

class MatchController extends Controller
{
    public function start_tournment() {
        $tour = Tournament::find(1);
        $tour->started = '1';
        $tour->save();

        $matches = Match::all();
        $teams = Team::all();
        $palyers = Player::all();

        $response = [
            "status" => "success",
            "data" => [
                "matches" => $matches,
                "teams" => $teams,
                "palyers" => $palyers,
            ],
        ];
        return response()->json($response, 200);
    }

    public function run() {
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

                    runAnOver($tour->id, $match->id, $innings, null, $batting_team_id, $bowling_team_id, $batsman1, $batsman2, $bowler, 0);
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
                    $b2 = getNextPlayer($tour->id, $match->id, $batting_team_id);

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

                    runAnOver($tour->id, $match->id, $innings, $last, $batting_team_id, $bowling_team_id, $batsman1, $batsman2, $bowler, $next_over);
                }
            }
        }
    }

    public function get_score(Request $request, $over) {
        $innings = Inning::where('id', '>', $over)->get();
        return $innings;
    }

    public function standings(Request $request) {
        $teams = Team::all();
        $list = [];
        foreach ($teams as $team) {
            $team1 = DB::table('matches')
                ->where('team1', $team->id)
                ->where('status', 2)
                ->groupBy('team1')
                ->count();
            $team2 = DB::table('matches')
                ->where('team2', $team->id)
                ->where('status', 2)
                ->groupBy('team2')
                ->count();

            $wins = DB::table('matches')
                ->where('team2', $team->id)
                ->where('winner_team', $team->id)
                ->where('status', 2)
                ->count();

            $total = $team1 + $team2;
            $lose = $total - $wins;
            array_push( $list, [
                'team_id' => $team->id,
                'name' => $team->name,
                'total' => $total,
                'wins' => $wins,
                'lose' => $lose,
            ]);
        }
        $response = [
            "status" => "success",
            "data" => $list,
        ];
        return response()->json($response, 200);
    }

    public function matches (Request $request, $id) {
        $matches = Match::all();
        $list = [];
        foreach ($matches as $match) {
            $team1 = Team::find($match->team1);
            $team2 = Team::find($match->team2);


        }
    }
}
