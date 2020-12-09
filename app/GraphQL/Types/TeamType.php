<?php

namespace App\GraphQL\Types;

use App\Models\Team;
use App\Traits\GameStats;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class TeamType
{
    use GameStats;

    public function games($root, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo)
    {
        return $root->homeGames->merge($root->awayGames);
    }

    public function team_stats($team, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $start_at = Arr::get($args, 'start_at');
        $end_at = Arr::get($args, 'end_at');
        $version = Arr::get($args, 'version');

        $games = $team->homeGames()
                      ->dates($start_at, $end_at)
                      ->version($version)
                      ->unionAll($team->awayGames()
                                      ->dates($start_at, $end_at)
                                      ->version($version))
                      ->get();

        $stats = $this->stats($games, $team);
        $response = $version ? $stats : $this->history($stats)->values();
        $team->stats = $response;
        return $response;
    }

    public function withGames($team, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $team->has('stats');
    }
}
