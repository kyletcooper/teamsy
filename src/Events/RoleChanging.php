<?php

namespace WRD\Teamsy\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use WRD\Teamsy\Capabilities\Role;

class RoleChanging
{
    use Dispatchable, SerializesModels;

    public Model $team;
	public Model $user;
    public Role $before;
    public Role $after;

    public function __construct(Model $team, Model $user, Role $before, Role $after)
    {
        $this->team = $team;
		$this->user = $user;
        $this->before = $before;
        $this->after = $after;
    }
}