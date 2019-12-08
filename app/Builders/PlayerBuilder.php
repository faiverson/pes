<?php

namespace App\Builders;

use App\Models\Player;
use Illuminate\Database\Eloquent\Builder;

class PlayerBuilder extends Builder
{
    public function name(string $name): Player
    {
        return $this->where('name', $name)->first();
    }
}
