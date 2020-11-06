<?php

namespace App\GraphQL\Queries;

use App\Models\Game;
use App\Models\Player;
use App\Traits\GameStats;
use Carbon\Carbon;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class PlayersVersus
{
    use GameStats;

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
    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $playerA = Arr::get($args, 'playerA');
        $playerB = Arr::get($args, 'playerB');
        $start_at = Arr::get($args, 'start_at');
        $end_at = Arr::get($args, 'end_at');
        $version = Arr::get($args, 'version');

        $player_home = Player::where('name', $playerA)->first();
        $player_away = Player::where('name', $playerB)->first();
//        Game::withPlayer($playerA)
        $query = Game::select('games.*', 'teamA.id as teamA_id', 'teamA.name as teamA_name', 'playerTeamA.name as playerA_name', 'playerTeamA.id as playerA_id',
        'teamB.id as teamB_id', 'teamB.name as teamB_name', 'playerTeamB.name as playerB_name', 'playerTeamB.id as playerB_id')
            ->join('teams as teamA', function ($join) {
                $join->on('games.team_home_id', '=', 'teamA.id')
                    ->orOn('games.team_away_id', '=', 'teamA.id');
            })
            ->join('players_teams as ptA', 'teamA.id', '=', 'ptA.team_id')
            ->join('players as playerTeamA', 'ptA.player_id', '=', 'playerTeamA.id')
            //second player joins
            ->join('teams as teamB', function ($join) {
                $join->on('games.team_home_id', '=', 'teamB.id')
                    ->orOn('games.team_away_id', '=', 'teamB.id');
            })
            ->join('players_teams as ptB', 'teamB.id', '=', 'ptB.team_id')
            ->join('players as playerTeamB', 'ptB.player_id', '=', 'playerTeamB.id')
            ->where('playerTeamA.id', $player_home->id)
            ->where('playerTeamB.id', $player_away->id)
            ->whereRaw('playerTeamA.id <> playerTeamB.id')
            ->whereRaw('teamA.id <> teamB.id')
            ->dates($start_at, $end_at)
            ->version($version);

        $games = $query->get();
        $total =  $games->count();
        $teams = $player_home->teams->pluck('id')->toArray();
        $win = $games->whereIn('team_home_id', $teams)->where('result', 'home')->count() + $games->whereIn('team_away_id', $teams)->where('result',
                'away')->count();
        $draw = $games->where('result', 'draw')->count();
        $lost = $games->whereIn('team_home_id', $teams)->where('result', 'away')->count() + $games->whereIn('team_away_id', $teams)->where('result',
                'home')->count();
        $gf = $games->whereIn('team_home_id', $teams)->sum('team_home_score') + $games->whereIn('team_away_id', $teams)->sum('team_away_score');
        $gc = $games->whereIn('team_home_id', $teams)->sum('team_away_score') + $games->whereIn('team_away_id', $teams)->sum('team_home_score');
        $result = $games->map(function ($game) {
            $scoreA = $game->teamA_id == $game->team_home_id ? $game->team_home_score : $game->team_away_score;
            $scoreB = $game->teamB_id == $game->team_home_id ? $game->team_home_score : $game->team_away_score;
            $date = new Carbon($game->created_at);
           return "{$game->teamA_name} {$scoreA}-{$scoreB} {$game->teamB_name} ({$date->format('d F Y')})";
        });

        return [
            'games' => $total,
            "results" => "{$player_home->name} {$win}-{$draw}-{$lost} {$player_away->name}",
            "score" => "{$player_home->name} {$gf}-{$gc} {$player_away->name}",
            'matches' => $result,
        ];
    }
}
