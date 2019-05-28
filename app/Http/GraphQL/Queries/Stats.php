<?php

namespace App\Http\GraphQL\Queries;

use App\Models\Game;
use App\Models\Team;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        $query = Game::with('team_home')->with('team_away');
        if($start_at) {
            $query = $query->whereDate('games.created_at', '>=', $start_at);
        }
        if($end_at) {
            $query = $query->whereDate('games.created_at', '<=', $end_at);
        }

        $games = $query->get();
        $teams = [];

        $games->map(function ($game) use(&$teams) {
            $date = new Carbon($game->created_at);
            $home_name = $game->team_home->name;
            $away_name = $game->team_away->name;
            if(!array_key_exists($home_name, $teams)) {
                $teams[$home_name] = $this->defaultTeamValues();
            }
            if(!array_key_exists($away_name, $teams)) {
                $teams[$away_name] = $this->defaultTeamValues();
            }
            $team_home = $teams[$home_name];
            $team_away = $teams[$away_name];
            $team_home['total'] += 1;
            $team_away['total'] += 1;
            $team_home['win'] += $game->result === 'home' ? 1 : 0;
            $team_away['win'] += $game->result === 'away' ? 1 : 0;
            $team_home['draw'] += $game->result === 'draw' ? 1 : 0;;
            $team_away['draw'] += $game->result === 'draw' ? 1 : 0;;
            $team_home['lost'] += $game->result === 'away' ? 1 : 0;
            $team_away['lost'] += $game->result === 'home' ? 1 : 0;
            $team_home['gf'] += (int) $game->team_home_score;
            $team_away['gf'] += (int) $game->team_away_score;
            $team_home['gc'] += (int) $game->team_away_score;
            $team_away['gc'] += (int) $game->team_home_score;
            $team_home['games'][] = "{$home_name} {$game->team_home_score}-{$game->team_away_score} {$away_name} ({$date->format('d F Y')})";
            $team_away['games'][] = "{$home_name} {$game->team_home_score}-{$game->team_away_score} {$away_name} ({$date->format('d F Y')})";
            $teams[$home_name] = $team_home;
            $teams[$away_name] = $team_away;
        });

        $results = [];
        foreach ($teams as $name => $team) {
            extract($team, EXTR_OVERWRITE);
            $results[$name] = [
                'avg' => $total > 0 ? number_format((($win * 3) + $draw) / ($total * 3) * 100, 0) . '%' : '0%',
                'games' => "{$total}",
                'record' => "{$win}-{$draw}-{$lost}",
                'difference' => $gf - $gc . " ({$gf}-{$gc})",
                'results' => $games,
            ];
        }


        return collect($results)->sortByDesc('avg');
    }

    private function defaultTeamValues()
    {
        return [
            'total' => 0,
            'win' => 0,
            'draw' => 0,
            'lost' => 0,
            'gf' => 0,
            'gc' => 0,
        ];
    }
}
