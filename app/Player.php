<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    public function teams()
    {
        return $this->belongsToMany('App\Team', 'team_players');
    }
}
