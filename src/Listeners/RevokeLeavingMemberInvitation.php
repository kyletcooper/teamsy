<?php

namespace WRD\Teamsy\Listeners;

use WRD\Teamsy\Events\LeavingTeam;

class RevokeLeavingMemberInvitation
{
    public function handle( LeavingTeam $event ) {
		$user = $event->user;
		$team = $event->team;

		// Remove the user's sent invitations when they leave a team.
		$team->invitations()
			->where( 'creator_id', $user->getKey() )
			->delete();
    }
}