<?php

namespace App\Traits;


use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait GameStats
{
    protected function stats($games, Team $team): Collection
    {
        return $games->groupBy('version')->map(function ($items, $version) use ($team) {
            $favor = $this->score($items, $team, true);
            $against =  $this->score($items, $team, false);
            $games = $items->count();
            $win = $this->win($items, $team);
            $draw = $this->draw($items);
            $lost = $this->lost($items, $team);
            $avg = $this->average($games, $win, $draw);
            return collect([
                'record' => $this->record($win, $draw, $lost, $favor, $against),
                'version' => $version,
                'games' => $games,
                'win' => $win,
                'draw' => $draw,
                'lost' => $lost,
                'gf' => $favor,
                'gc' => $against,
                'difference' => $this->difference($favor, $against),
                'average' => $avg . '%',
                'avg' => (float) $avg,
                'matches' => $this->matches($items)
            ]);
        })->sortKeysDesc();
    }

    protected function record($win, $draw, $lost, $gf, $gc): string
    {
        return "{$win}-{$draw}-{$lost} ({$gf}-{$gc})";
    }

    protected function average($games, $win, $draw): string
    {
        return $games > 0 ? number_format((($win * 3) + $draw) / ($games * 3) * 100, 2)  : '0%';
    }

    protected function difference($favor, $against): int
    {
        return $favor - $against;
    }

    protected function matches(Collection $games)
    {
        return $games->map(function ($match) {
            return "{$match->teamHome->name} {$match->team_home_score}-{$match->team_away_score} {$match->teamAway->name} ({$this->played($match->created_at)})";
        });
    }

    protected function score(Collection $games, Team $team, bool $own): int
    {
        $t = ['team_home_score', 'team_away_score'];
        $t = $own ? $t : array_reverse($t);
        return $games->where('team_home_id', $team->id)->sum($t[0]) +
            $games->where('team_away_id', $team->id)->sum($t[1]);
    }

    protected function win(Collection $games, Team $team): int
    {
        return $games->where('team_home_id', $team->id)
                ->where('result', 'home')
                ->count()
            +  $games->where('team_away_id', $team->id)
                ->where('result', 'away')
                ->count();
    }

    protected function draw(Collection $games): int
    {
        return $games->where('result', 'draw')->count();
    }

    protected function lost(Collection $games, Team $team): int
    {
        return $games->where('team_home_id', $team->id)
                ->where('result', 'away')
                ->count()
            +  $games->where('team_away_id', $team->id)
                ->where('result', 'home')
                ->count();
    }

    protected function played($date): string
    {
        return $date->format('M d, Y');
    }

    protected function history(Collection $collection, bool $add = true): Collection
    {
        $total = $collection->pipe(function ($collection) {
            $games = $collection->sum('games');
            $win = $collection->sum('win');
            $lost = $collection->sum('lost');
            $draw = $collection->sum('draw');
            $gf = $collection->sum('gf');
            $gc = $collection->sum('gc');
            $avg = $this->average($games, $win, $draw);
            return collect([
                'version' => 'PES TOTAL',
                'games' => $games,
                'win' => $win,
                'draw' => $draw,
                'lost' => $lost,
                'gf' => $gf,
                'gc' => $gc,
                'difference' => $this->difference($gf, $gc),
                'record' => $this->record($win, $draw, $lost, $gf, $gc),
                'average' => $avg . "%",
                'avg' => (float) $avg,
                'matches' => $collection->pluck('matches')->flatten()
            ]);
        });
        return $add ? $collection->prepend($total, 'PES TOTAL') : collect(['PES TOTAL' => $total]);
    }
}
