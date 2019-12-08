<?php

namespace App\Builders;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

class GameBuilder extends Builder
{
    public function withPlayer(Player $player): Builder
    {
        return $this->where(function ($q) use($player) {
            $q->whereHas('teamHome.players', function ($q) use($player) {
                $q->where('players.id', $player->id);
            })
            ->orWhereHas('teamAway.players', function ($q) use($player) {
                $q->where('players.id', $player->id);
            });
        });
    }

    public function versus(Team $home, Team $away): Builder
    {
        return $this->where(function ($q) use($home, $away) {
            $q->where(function ($q) use($home, $away) {
                $q->where('team_home_id', $home->id)->where('team_away_id', $away->id);
            })
            ->orWhere(function ($q) use($home, $away) {
                $q->where('team_home_id', $away->id)->where('team_away_id', $home->id);
            });
        });
    }

    public function dates(string $start_at = null, string $end_at = null): Builder
    {
        if($start_at) {
            $this->whereDate('created_at', '>=', $start_at);
        }
        if($end_at) {
            $this->whereDate('created_at', '<=', $end_at);
        }
        return $this;
    }

    public function version(string $version = null): Builder
    {
        if($version) {
            $this->where('version', $version);
        }
        return $this;
    }
}
