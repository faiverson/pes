<?php

namespace App\Models;

use App\Builders\GameBuilder;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Game extends BaseModel
{
    protected $builder = GameBuilder::class;

    protected $table = 'games';

    protected $with = ['teamHome', 'teamAway'];

    protected $fillable = [
        'team_home_id',
        'team_away_id',
        'team_home_score',
        'team_away_score',
        'result',
        'version',
        'created_at',
        'updated_at',
    ];

    public function teamHome(): HasOne
    {
        return $this->hasOne(Team::class, 'id', 'team_home_id');
    }

    public function teamAway(): HasOne
    {
        return $this->hasOne(Team::class, 'id', 'team_away_id');
    }
}
