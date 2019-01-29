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
        $start_at = array_get($args, 'start_at');
        $end_at = array_get($args, 'end_at');
        $first_team = Team::find($first_team_id);
        $second_team = Team::find($second_team_id);

        $query = Game::where(function ($q) use($first_team_id, $second_team_id, $start_at, $end_at) {
                            $q->where(function ($q) use($first_team_id, $second_team_id, $start_at, $end_at) {
                                $q->where('team_home_id', $first_team_id)->where('team_away_id', $second_team_id);
                            })
                            ->orWhere(function ($q) use($first_team_id, $second_team_id) {
                                $q->where('team_home_id', $second_team_id)->where('team_away_id', $first_team_id);
                            });
                        });


        if($start_at) {
            $query = $query->whereDate('created_at', '>=', $start_at);
        }
        if($end_at) {
            $query = $query->whereDate('created_at', '<=', $end_at);
        }

        $games = $query->get();
        if($games->isEmpty()) {
            return [
                'games' => 'No data found',
            ];
        }
        $gf = $games->where('team_home_id', $first_team_id)->sum('team_home_score') + $games->where('team_away_id', $first_team_id)->sum('team_away_score');
        $gc = $games->where('team_home_id', $second_team_id)->sum('team_home_score') + $games->where('team_away_id', $second_team_id)->sum('team_away_score');
        $matches = $games->map(function ($game) use($first_team_id) {
            if($game->team_home->id == $first_team_id) {
                $response = "{$game->team_home->name} {$game->team_home_score}-{$game->team_away_score} {$game->team_away->name} ({$game->created_at->format('d F Y')})";
            }
            else {
                $response = "{$game->team_away->name} {$game->team_away_score}-{$game->team_home_score} {$game->team_home->name} ({$game->created_at->format('d F Y')})";
            }

            return $response;
        });

        return [
            'total' => $games->count(),
            $first_team->name => $games->where('team_home_id', $first_team_id)->where('result', 'home')->count() + $games->where('team_away_id', $first_team_id)->where('result', 'away')->count(),
            'draw' => $games->where('result', 'draw')->count(),
            $second_team->name => $games->where('team_home_id', $second_team_id)->where('result', 'home')->count() + $games->where('team_away_id', $second_team_id)->where('result', 'away')->count(),
            'Difference' => ($gf - $gc) . " ({$gf}-{$gc})",
            'Games' => $matches
        ];
    }
}
