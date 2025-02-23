<?php

namespace WRD\Teamsy\Policies;

use Illuminate\Database\Eloquent\Model;
use WRD\Teamsy\Models\Invitation;

class InvitationPolicy{
	/**
	 * Determine if an invitation can be created by the user.
	 */
	public function viewAny( Model $user, Model $team ): bool
	{
		return $team->userCan( $user, 'invitation.viewAny' );
	}

	/**
	 * Determine if an invitation can be created by the user.
	 */
	public function create( Model $user, Model $team ): bool
	{
		return $team->userCan( $user, 'invitation.create' );
	}

	/**
	 * Determine if the given invitation can be viewed by the user.
	 */
	public function view( Model $user, Invitation $invitation): bool
	{
		if( $invitation->getTeam()->userCan( $user, 'invitation.view' ) ){
			return true;
		}

		if( $invitation->getInvitee()?->is( $user ) ){
			return true;
		}

		return false;
	}

	/**
	 * Determine if an invitation can be revoked by the user.
	 */
	public function destroy( Model $user, Invitation $invitation): bool
	{
		return $invitation->getTeam()->userCan( $user, 'invitation.destroy' );
	}

	/**
	 * Determine if an invitation can be declined/accepted by the user.
	 */
	public function respond( Model $user, Invitation $invitation): bool
	{
		return $invitation->getInvitee()?->is( $user ) ?? false;
	}
}