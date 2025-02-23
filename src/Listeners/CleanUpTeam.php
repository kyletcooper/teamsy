<?php

namespace WRD\Teamsy\Listeners;

use WRD\Teamsy\Events\MemberDeleting;
use WRD\Teamsy\Events\TeamDeleting;

class CleanUpTeam
{
    public function handle( TeamDeleting $event ) {
		$team = $event->team;

		// Delete invitations to the team.
		$team->invitations()->delete();

		// Delete memberships to the team.
		$team->members()->detach();
    }
}