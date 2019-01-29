<?php

namespace App\Http\GraphQL\Types;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class TeamType
{
    public function games($root, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo)
    {
        return $root->homeGames->merge($root->awayGames);
    }

    public function results($root, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo)
    {
        $matches = $root->homeGames->merge($root->awayGames)->map(function ($game) use($root) {
            if($game->team_home->id == $root->id) {
                $response = "{$game->team_home->name} {$game->team_home_score}-{$game->team_away_score} {$game->team_away->name} ({$game->created_at->format('d F Y')})";
            }
            else {
                $response = "{$game->team_away->name} {$game->team_away_score}-{$game->team_home_score} {$game->team_home->name} ({$game->created_at->format('d F Y')})";
            }

            return $response;
        });

        return $matches;
    }
}
