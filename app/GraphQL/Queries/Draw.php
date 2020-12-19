<?php

namespace App\GraphQL\Queries;

use App\Models\Player;
use App\Models\Team;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Draw
{
    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $participants = collect(Arr::get($args, 'participants'));
        $time = Arr::get($args, 'time');

        $names = $participants->pluck('name')->values();
        $players = Player::whereIn('name', $names)->get();

        if($players->count() !== $names->count()) {
            throw new \Exception('Player/s not found');
        }

        $players->transform(function ($player) use($participants) {
        $participant = (object) $participants->where('name', $player->name)->first();
            $player->weight = $participant->weight;
            return $player;
        });

        $total = $players->count();
        $median = $players->median('weight');
        $groups = $players->sortBy('weight')->mapToGroups(function ($player) use($median) {
            $key = $player->weight <= $median ? 'weak' : 'strong';
            return [$key => $player];
        });

        $strong = $groups->get('strong');
        $weak = $groups->get('weak');
        if($strong->count() > $weak->count()) {
            $this->balance($strong, $weak);
        } elseif($weak->count() > $strong->count()) {
            $this->balance($weak, $strong);
        }

        $strong = $strong->shuffle();
        $weak = $weak->shuffle();
        $teams = [];
        for($i = 0; $i < ($total/2); $i++) {
            $playerStrong = $strong[$i];
            $playerWeak = $weak[$i];

            $teams[] = Team::whereHas('players', function($q) use($playerStrong) {
                $q->where('players.id', $playerStrong->id);
            })->whereHas('players', function($q) use($playerWeak) {
                $q->where('players.id', $playerWeak->id);
            })->first();
        }

        return $teams;
//        dd($c->pluck('name')->all(), $strong->shuffle()->all());

    }
    private function balance(&$bigger, &$lower)
    {
        $half = ($bigger->count() + $lower->count()) / 2;
        while($bigger->count() > $half) {
            $item = $bigger->shift();
            $lower->push($item);
        }
    }
}
