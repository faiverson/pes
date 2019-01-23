<?php

namespace App\Http\GraphQL\Queries;

use App\Models\Game;
use App\Models\Team;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Versus
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
        $first_team_id = array_get($args, 'first_team_id');
        $second_team_id = array_get($args, 'second_team_id');
        $summary = array_get($args, 'summary', false);
        $first_team = Team::find($first_team_id);
        $second_team = Team::find($second_team_id);

        $games = Game::where('team_home_id', $first_team_id)->where('team_away_id', $second_team_id)->get();

        $first_home = [
            'games' => $games->count(),
            'win' => $games->where('result', 'home')->count(),
            'draw' => $games->where('result', 'draw')->count(),
            'lost' => $games->where('result', 'away')->count(),
            'gf' => $games->sum('team_home_score'),
            'gc' => $games->sum('team_away_score'),
        ];
        $second_away = [
            'games' => $first_home['games'],
            'win' => $first_home['lost'],
            'draw' => $first_home['lost'],
            'lost' => $first_home['win'],
            'gf' => $first_home['gc'],
            'gc' => $first_home['gf'],
        ];

        $games = Game::where('team_home_id', $second_team_id)->where('team_away_id', $first_team_id)->get();
        $first_away = [
            'games' => $games->count(),
            'win' => $games->where('result', 'away')->count(),
            'draw' => $games->where('result', 'draw')->count(),
            'lost' => $games->where('result', 'draw')->count(),
            'gf' => $games->sum('team_away_score'),
            'gc' => $games->sum('team_home_score'),
        ];
        $second_home = [
            'games' => $first_away['games'],
            'win' => $first_away['lost'],
            'draw' => $first_away['lost'],
            'lost' => $first_away['win'],
            'gf' => $first_away['gc'],
            'gc' => $first_away['gf'],
        ];

        $first_global = [];
        $second_global = [];
        foreach ($first_home as $key => $item) {
            $first_global[$key] = $item + $first_away[$key];
        }

        foreach ($second_home as $key => $item) {
            $second_global[$key] = $item + $second_away[$key];
        }
        if($summary) {
            return [
                $first_team->name => $first_global['win'],
                'Draw' => $first_global['draw'],
                $second_team->name => $second_global['win']
            ];
        }
        return [
            $first_team->name => [
                'total' => $first_global,
                'home' => $first_home,
                'away' => $first_away,
                'record' => "{$first_global['win']}-{$first_global['draw']}-{$first_global['lost']}"
            ],
            $second_team->name => [
                'total' => $second_global,
                'home' => $second_home,
                'away' => $second_away,
                'record' => "{$second_global['win']}-{$second_global['draw']}-{$second_global['lost']}"
            ]
        ];
    }
}
