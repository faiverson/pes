<?php

namespace App\Builders;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

class TeamBuilder extends Builder
{
    public function name(string $name): Team
    {
        return $this->where('name', $name)->first();
    }

    public function games()
    {
        return $this->homeGames->merge($this->awayGames);
    }
}
