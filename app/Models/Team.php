<?php

namespace App\Models;

use App\Builders\TeamBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\hasMany;

class Team extends BaseModel
{
    protected $builder = TeamBuilder::class;

    protected $table = 'teams';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'players_teams', 'team_id', 'player_id');
    }

    public function matches()
    {
        return $this->homeGames()->unionAll($this->awayGames());
    }

    public function homeGames(): hasMany
    {
        return $this->hasMany(Game::class, 'team_home_id');
    }

    public function awayGames(): hasMany
    {
        return $this->hasMany(Game::class, 'team_away_id');
    }
}
