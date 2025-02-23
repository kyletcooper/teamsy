<?php

namespace WRD\Teamsy\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class TeamCreated
{
    use Dispatchable, SerializesModels;

    public Model $team;

    public function __construct(Model $team)
    {
        $this->team = $team;
    }
}