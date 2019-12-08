<?php

namespace App\Models;

use App\Builders\PlayerBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Player extends BaseModel
{
    protected $builder = PlayerBuilder::class;

    protected $table = 'players';

    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'players_teams', 'player_id');
    }
}
