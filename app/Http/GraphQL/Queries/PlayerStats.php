<?php

namespace App\Http\GraphQL\Queries;

use App\Models\Game;
use App\Models\Player;
use Carbon\Carbon;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class PlayerStats
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
        $name = array_get($args, 'name');
        $start_at = array_get($args, 'start_at');
        $end_at = array_get($args, 'end_at');
        $player = Player::where('name', $name)->first();
        if(empty($player)) {
            throw new \Exception('wrong name');
        }

        $query = Game::where(function ($q) use($player) {
            $q->whereHas('team_home.players', function ($q) use($player) {
                $q->where('players.id', $player->id);
            })
            ->orWhereHas('team_away.players', function ($q) use($player) {
                $q->where('players.id', $player->id);

            });
        });

        if($start_at) {
            $query = $query->whereDate('games.created_at', '>=', $start_at);
        }
        if($end_at) {
            $query = $query->whereDate('games.created_at', '<=', $end_at);
        }

        $games = $query->get();
        $total = $games->count();
        $teams = $player->teams->pluck('id')->toArray();
        $win = $games->whereIn('team_home_id', $teams)->where('result', 'home')->count() + $games->whereIn('team_away_id', $teams)->where('result',
                'away')->count();
        $draw = $games->where('result', 'draw')->count();
        $lost = $games->whereIn('team_home_id', $teams)->where('result', 'away')->count() + $games->whereIn('team_away_id', $teams)->where('result',
                'home')->count();
        $gf = $games->whereIn('team_home_id', $teams)->sum('team_home_score') + $games->whereIn('team_away_id', $teams)->sum('team_away_score');
        $gc = $games->whereIn('team_home_id', $teams)->sum('team_away_score') + $games->whereIn('team_away_id', $teams)->sum('team_home_score');
        $result = $games->map(function ($game) {
            $date = new Carbon($game->created_at);
            return "{$game->team_home->name} {$game->team_home_score}-{$game->team_away_score} {$game->team_away->name} ({$date->format('d F Y')})";
        });

        return [
            'name' => $player->name,
            'avg' => $total > 0 ? number_format((($win * 3) + $draw) / ($total * 3) * 100, 0) . '%' : '0%',
            'record' => "{$win}-{$draw}-{$lost}",
            'games' => $total,
            'difference' => $gf - $gc . " ({$gf}-{$gc})",
            'games' => $total,
            'win' => $win,
            'draw' => $draw,
            'lost' => $lost,
            'gf' => $gf,
            'results' => $result,
        ];
    }
}
