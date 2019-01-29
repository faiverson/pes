<?php

namespace App\Http\GraphQL\Queries;

use App\Models\Game;
use App\Models\Player;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class PlayersStats
{
    /**
     * Return a value for the field.
     *
     * @param null $rootValue Usually contains the result returned from the parent field. In this case, it is always `null`.
     * @param array $args The arguments that were passed into the field.
     * @param GraphQLContext|null $context Arbitrary data that is shared between all fields of a single query.
     * @param ResolveInfo $resolveInfo Information about the query itself, such as the execution state, the field name, path to the field from the root, and more.
     *
     * @return mixed
     */
    public function resolve($rootValue, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo)
    {
        $start_at = array_get($args, 'start_at');
        $end_at = array_get($args, 'end_at');

        $query = DB::table('games')->select('players.name', 'games.*', 'teams.name as team_name', 'teams.id as team_id')
            ->join('teams', function ($join) {
                $join->on('games.team_home_id', '=', 'teams.id')
                    ->orOn('games.team_away_id', '=', 'teams.id');
            })
            ->join('players_teams', 'teams.id', '=', 'players_teams.team_id')
            ->join('players', 'players_teams.player_id', '=', 'players.id');

        if($start_at) {
            $query = $query->whereDate('games.created_at', '>=', $start_at);
        }
        if($end_at) {
            $query = $query->whereDate('games.created_at', '<=', $end_at);
        }

        $players = $query->get();
        $result = $players->groupBy('name')->map(function ($games) {
            $teams = $games->pluck('team_id')->unique()->toArray();
            $total =  $games->count();


            $win = $games->whereIn('team_home_id', $teams)->where('result', 'home')->count() + $games->whereIn('team_away_id', $teams)->where('result',
                    'away')->count();
            $draw = $games->where('result', 'draw')->count();
            $lost = $games->whereIn('team_home_id', $teams)->where('result', 'away')->count() + $games->whereIn('team_away_id', $teams)->where('result',
                    'home')->count();

            $gf = $games->whereIn('team_home_id', $teams)->sum('team_home_score') + $games->whereIn('team_away_id', $teams)->sum('team_away_score');
            $gc = $games->whereIn('team_home_id', $teams)->sum('team_away_score') + $games->whereIn('team_away_id', $teams)->sum('team_home_score');
            return [
                'avg' => $total > 0 ? number_format((($win * 3) + $draw) / ($total * 3) * 100, 0) . '%' : '0%',
                'record' => "{$win}-{$draw}-{$lost}",
                'difference' => $gf - $gc . " ({$gf}-{$gc})",
                'games' => $total,
                'win' => $win,
                'draw' => $draw,
                'lost' => $lost,
                'gf' => $gf,
                'gc' => $gc,
            ];
        });

        return collect($result)->sortByDesc('avg');
    }
}
