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

        $strong = $groups->get('strong') ?? collect();
        $weak = $groups->get('weak') ?? collect();

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

        $response = [
            'modality' => $strong->merge($weak)->shuffle()->first(),
            'teams' => $teams,
        ];

        $this->whatsapp($response);
        return $response;
    }

    private function balance(&$bigger, &$lower)
    {
        $half = ($bigger->count() + $lower->count()) / 2;
        while($bigger->count() > $half) {
            $item = $bigger->shift();
            $lower->push($item);
        }
    }

    private function whatsapp($response)
    {
        $teams = collect(Arr::get($response, 'teams'));
        $player = Arr::get($response, 'modality');
        $team_txt = implode("\n", $teams->pluck('name')->all());
        $url = "https://messages-sandbox.nexmo.com/v0.1/messages";
        $text = "*Modalidad:* $player->name\n*Equipos:*\n$team_txt";

        $phones = [
            '5493516223135', // fabian
            '5493513811489', // juan
            '5493516371891', // gabi
            '5493516852080', // tincho
            '5493516806389', // horacio
//            '5493512353460',
//            '5493516142986',
            '5493512001308', // luciano
        ];

        foreach ($phones as $phone) {
            $params = [
                "from" => ["type" => "whatsapp", "number" => "14157386170"],
                "to" => ["type" => "whatsapp", "number" => $phone],
                "message" => [
                    "content" => [
                        "type" => "text",
                        "text" => $text
                    ]
                ]
            ];
            $headers = [
                "Authorization" => "Basic " . base64_encode(env('NEXMO_API_KEY') . ":" . env('NEXMO_API_SECRET')),
                "Content-Type" => "application/json",
                "Accept" => "application/json",
            ];

            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $url, ["headers" => $headers, "json" => $params]);
            $data[]  = $response->getBody()->getContents();
        }
    }
}
