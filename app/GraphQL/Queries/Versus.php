<?php

namespace App\GraphQL\Queries;

use App\Models\Game;
use App\Models\Team;
use App\Traits\GameStats;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Versus
{
    use GameStats;

    /**
     * Return a value for the field.
     *
     * @param  null  $rootValue Usually contains the result returned from the parent field. In this case, it is always `null`.
     * @param  mixed[]  $args The arguments that were passed into the field.
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context Arbitrary data that is shared between all fields of a single query.
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo Information about the query itself, such as the execution state, the field name, path to the field from the root, and more.
     * @return mixed
     */
    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $first_team_id = Arr::get($args, 'first_team_id');
        $second_team_id = Arr::get($args, 'second_team_id');
        $start_at = Arr::get($args, 'start_at');
        $end_at = Arr::get($args, 'end_at');
        $version = Arr::get($args, 'version');
        $first_team = Team::find($first_team_id);
        $second_team = Team::find($second_team_id);

        $games = Game::versus($first_team, $second_team)
            ->dates($start_at, $end_at)
            ->version($version)
            ->get();

        if ($games->isEmpty()) {
            return null;
        }

        $stats = $this->stats($games, $first_team);
        return ($version ? $stats : $this->history($stats, false))->values();
    }
}
