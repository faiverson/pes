<?php

namespace App\Http\GraphQL\Queries;

use App\Models\Team;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Stats
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
        $query = Team::orderBy('id');
        if($start_at || $end_at) {
            $query = $query->whereHas('homeGames', function ($query) use($start_at, $end_at) {
                if($start_at) {
                    $query->whereDate('games.created_at', '>=', $start_at);
                }
                if($end_at) {
                    $query->whereDate('games.created_at', '<=', $end_at);
                }
            })
            ->orWhereHas('awayGames', function ($query) use($start_at, $end_at) {
                if($start_at) {
                    $query->whereDate('games.created_at', '<=', $start_at);
                }
                if($end_at) {
                    $query->whereDate('games.created_at', '<=', $end_at);
                }
            });
        }

        $teams = $query->get();
        $result = [];

        //@TODO FILTERS ARE NOT WORKING EBCAUSE IT IS FINDING TEAMS!
        foreach ($teams as $team) {
            $games = $team->homeGames->count() + $team->awayGames->count();
            $win = $team->homeGames->where('result', 'home')->count() + $team->awayGames->where('result', 'away')->count();
            $draw = $team->homeGames->where('result', 'draw')->count() + $team->awayGames->where('result', 'draw')->count();
            $lost = $team->homeGames->where('result', 'away')->count() + $team->awayGames->where('result', 'home')->count();
            $gf = $team->homeGames->sum('team_home_score') + $team->awayGames->sum('team_away_score');
            $gc = $team->homeGames->sum('team_away_score') + $team->awayGames->sum('team_home_score');
            $result[$team->name] = [
                'avg' => $games > 0 ? number_format((($win * 3) + $draw) / ($games * 3) * 100, 0) . '%' : '0%',
                'games' => $games,
                'record' => "{$win}-{$draw}-{$lost}",
                'difference' => $gf - $gc . " ({$gf}-{$gc})",
//                'gc' => $gc,
//                'gf' => $gf,
//                'lost' => $lost,
//                'draw' => $draw,
//                'win' => $win,
            ];

        }
        return collect($result)->sortByDesc('avg');
    }
}
