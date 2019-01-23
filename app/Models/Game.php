<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'games';

    protected $fillable = [
        'team_home_id',
        'team_away_id',
        'team_home_score',
        'team_away_score',
        'result',
    ];

    public $timestamps = true;

    public function team_home()
    {
        return $this->hasOne(Team::class, 'id', 'team_home_id');
    }

    public function team_away()
    {
        return $this->hasOne(Team::class, 'id', 'team_away_id');
    }
}
