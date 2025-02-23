<?php

namespace WRD\Teamsy\Listeners;

use WRD\Teamsy\Events\MemberDeleting;

class CleanUpMember
{
    public function handle( MemberDeleting $event ) {
		$user = $event->member;

        // Delete invitations sent by the user.
		$user->sentInvitations()->forceDelete();

		// Delete all memberships for the user.
		foreach( $user->getAllTeamRelationships() as $relationship ){
			$user->{$relationship}()->delete();
		}
    }
}