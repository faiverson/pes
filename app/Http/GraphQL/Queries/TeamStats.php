<?php

namespace App\Http\GraphQL\Queries;

use App\Models\Team;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class TeamStats
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
        $id = array_get($args, 'id');
        $team = Team::find($id);

        $home = [
            'games' => $team->homeGames->count(),
            'win' => $team->homeGames->where('result', 'home')->count(),
            'draw' => $team->homeGames->where('result', 'draw')->count(),
            'lost' => $team->homeGames->where('result', 'away')->count(),
            'gf' => $team->homeGames->sum('team_home_score'),
            'gc' => $team->homeGames->sum('team_away_score'),
        ];
        $first_away = [
            'games' => $team->awayGames->count(),
            'win' => $team->awayGames->where('result', 'away')->count(),
            'draw' => $team->awayGames->where('result', 'draw')->count(),
            'lost' => $team->awayGames->where('result', 'draw')->count(),
            'gf' => $team->awayGames->sum('team_away_score'),
            'gc' => $team->awayGames->sum('team_home_score'),
        ];

        $global = [];
        foreach ($home as $key => $item) {
            $global[$key] = $item + $first_away[$key];
        }
        $avg = $global['games'] > 0 ? number_format((($global['win'] * 3) + $global['draw']) / ($global['games'] * 3) * 100, 0) . '%' : '0%';
        return [
            'name' => $team->name,
            'average' => $avg,
            'record' => "{$global['win']}-{$global['draw']}-{$global['lost']}",
//            'global' => $global,
//            'home' => $home,
//            'away' => $first_away
        ];
    }
}
