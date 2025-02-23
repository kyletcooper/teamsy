<?php

namespace WRD\Teamsy\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use WRD\Teamsy\Capabilities\Role;

class JoiningTeam
{
    use Dispatchable, SerializesModels;

    public Model $team;
	public Model $user;
    public Role $role;

    public function __construct(Model $team, Model $user, Role $role)
    {
        $this->team = $team;
		$this->user = $user;
        $this->role = $role;
    }
}