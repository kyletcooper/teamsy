<?php

namespace WRD\Teamsy\Listeners;

use WRD\Teamsy\Events\MemberDeleting;
use WRD\Teamsy\Models\TeamInspector;

class CleanUpMember
{
    public function handle( MemberDeleting $event ) {
		$inspector = new TeamInspector();
		$user = $event->member;

        // Delete invitations sent by the user.
		$user->sentInvitations()->forceDelete();

		// Delete all memberships for the user.
		foreach( $inspector->getTeamRelationships( $user ) as $relationship ){
			$user->{$relationship}()->delete();
		}
    }
}