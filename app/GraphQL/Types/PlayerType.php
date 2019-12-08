<?php

namespace App\GraphQL\Types;

use App\Models\Game;
use App\Traits\GameStats;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class PlayerType
{
    use GameStats;

    public function stats($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $player = $rootValue;
        $start_at = Arr::get($args, 'start_at');
        $end_at = Arr::get($args, 'end_at');
        $version = Arr::get($args, 'version');

        $games = Game::withPlayer($player)
            ->dates($start_at, $end_at)
            ->version($version)
            ->get();

        if ($games->isEmpty()) {
            return null;
        }

        $stats = $games->groupBy('version')->map(function ($items, $version) use ($player) {
            $teams = $player->teams->pluck('id')->toArray();
            $games = $items->count();
            $favor = $items->whereIn('team_home_id', $teams)->sum('team_home_score');
            $favor += $items->whereIn('team_away_id', $teams)->sum('team_away_score');;
            $against = $items->whereIn('team_home_id', $teams)->sum('team_away_score');
            $against += $items->whereIn('team_away_id', $teams)->sum('team_home_score');
            $win = $items->whereIn('team_home_id', $teams)->where('result', 'home')->count();
            $win += $items->whereIn('team_away_id', $teams)->where('result', 'away')->count();
            $draw = $this->draw($items);
            $lost = $items->whereIn('team_home_id', $teams)->where('result', 'away')->count();
            $lost += $items->whereIn('team_away_id', $teams)->where('result', 'home')->count();
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
                'average' => $this->average($games, $win, $draw),
                'matches' => $this->matches($items)
            ]);
        })->sortKeysDesc();

        return ($version ? $stats : $this->history($stats, false))->values();
    }
}
