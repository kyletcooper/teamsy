<?php

namespace WRD\Teamsy\Listeners;

use WRD\Teamsy\Events\RoleChanging;

class RevokeNoLongerAllowedInvitations
{
    public function handle( RoleChanging $event ) {
		$role = $event->after;
		$user = $event->user;
		$team = $event->team;

		$canCreate = $team->roleCan( $role, 'invitation.create' );

		if( ! $canCreate ){
			// Revoke the user's pending invitations -- they are no longer allowed to send invites.
			$event->team->invitations()
				->where( 'creator_id', $user->getKey() )
				->delete();
		}
    }
}