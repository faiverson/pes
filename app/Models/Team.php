<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\hasMany;

class Team extends Model
{
    protected $table = 'teams';

    protected $fillable = [
        'name',
    ];

    public $timestamps = FALSE;

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
