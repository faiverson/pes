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
        $name = array_get($args, 'name');
        $team = $id > 0 ? Team::find($id) : Team::where('name', $name)->first();

        $response = $team->matches->groupBy('version')->map(function ($items, $version) use($team) {
            $id = $team->id;
            $data = [
                'version' => $version,
                'games' => $items->count(),
                'win'  => $items->where('team_home_id', $id)->where('result', 'home')->count() + $items->where('team_away_id', $id)->where('result', 'away')->count(),
                'lost' => $items->where('team_home_id', $id)->where('result', 'away')->count() + $items->where('team_away_id', $id)->where('result', 'home')->count(),
                'draw' => $items->where('result', 'draw')->count(),
                'gf'   => $items->where('team_home_id', $id)->sum('team_home_score') + $items->where('team_away_id', $id)->sum('team_away_score'),
                'gc'   => $items->where('team_home_id', $id)->sum('team_away_score') + $items->where('team_away_id', $id)->sum('team_home_score'),
            ];

            $data['difference'] = "{$data['gf']}-{$data['gc']}";
            $data['record'] = "{$data['win']}-{$data['draw']}-{$data['lost']} ({$data['gf']}-{$data['gc']})";
            $data['average'] = $data['games'] > 0 ? number_format((($data['win'] * 3) + $data['draw']) / ($data['games'] * 3) * 100, 0) . '%' : '0%';
            $data['matches'] = $items->map(function ($match) {;
                return "{$match->team_home->name} {$match->team_home_score}-{$match->team_away_score} {$match->team_away->name} ({$match->created_at->format('F d, Y')})" ;
            })->all();
            return $data;
        });

        $response->push($response->pipe(function ($collection) {
            $total = [
                'version' => 'PES HISTORY',
                'games' => $collection->sum('games'),
                'win' => $collection->sum('win'),
                'lost' => $collection->sum('lost'),
                'draw' => $collection->sum('draw'),
                'gf' => $collection->sum('gf'),
                'gc' => $collection->sum('gc')
            ];

            $total['matches'] = $collection->pluck('matches')->flatten();
            $total['difference'] = "{$total['gf']}-{$total['gc']}";
            $total['record'] = "{$total['win']}-{$total['draw']}-{$total['lost']} ({$total['gf']}-{$total['gc']})";
            $total['average'] = $total['games'] > 0 ? number_format((($total['win'] * 3) + $total['draw']) / ($total['games'] * 3) * 100, 0) . '%' : '0%';
            return $total;
        }));

        $team->stats = $response->values();
        return $team;
    }
}
