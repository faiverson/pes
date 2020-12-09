<?php

namespace App\GraphQL\Queries;

use App\GraphQL\Types\TeamType;
use App\Models\Game;
use App\Models\Team;
use App\Traits\GameStats;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class TeamStats
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
        $version = Arr::get($args, 'version');
        $stats = new TeamType();
        $teams = Team::all();
        $teams = $teams->map(function ($team) use($stats, $args, $context, $resolveInfo, $version) {
          $team->stats = $stats->team_stats($team, $args, $context, $resolveInfo);
          if($version) {
            $team->stats = $team->stats->get($version);
          } else {
            $team->stats = $team->stats->get('PES_TOTAL');
          }

          if(!is_null($team->stats)) {
            $team->record = $team->stats['record'];
            $team->avg = $team->stats['avg'];
            $team->average = $team->stats['average'];
          } else {
            $team->average = null;
          }

          return $team;
        })->reject(function ($team) {
          return is_null($team->record);
        })->sortByDesc(function ($team) {
          return $team->avg;
        });

        return $teams->map(function ($team) {
          return [
            'id' => $team->id,
            'name' => $team->name,
            'average' => $team->average,
            'record' => $team->record,
          ];
        })->values();
    }
}
