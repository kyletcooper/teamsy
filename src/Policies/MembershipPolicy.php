<?php

namespace WRD\Teamsy\Policies;

use Illuminate\Database\Eloquent\Model;
use WRD\Teamsy\Capabilities\Role;
use WRD\Teamsy\Capabilities\RoleFlag;
use WRD\Teamsy\Models\Membership;

class MembershipPolicy{
	/**
	 * Determine if all memberships can be viewed by the user.
	 */
	public function viewAny( Model $user, Model $team ): bool
	{
		return $team->userCan( $user, 'membership.viewAny' );
	}

	/**
	 * Determine if the given membership can be viewed by the user.
	 */
	public function view( Model $user, Membership $membership): bool
	{
		if( $membership->getTeam()->userCan( $user, 'membership.view' ) ){
			return true;
		}

		if( $membership->getMember()->is( $user ) ){
			return true;
		}

		return false;
	}

	/**
	 * Determine if the given membership can be updated by the user.
	 */
	public function update( Model $user, Membership $membership): bool
	{
		return $membership->getTeam()->userCan( $user, 'membership.update' );
	}

	/**
	 * Determine if the role of a given membership can be updated by the user.
	 */
	public function updateRole( Model $user, Membership $membership, Role $newRole ): bool
	{
		if( $membership->getRole()->hasFlag( RoleFlag::Owner ) ){
			// Nobody can alter the owner's role.
			return false;
		}

		if( $newRole->hasFlag( RoleFlag::Owner ) ){
			// You may not premote yourself to owner.
			return false;
		}

		return $membership->getTeam()->userCan( $user, 'membership.update' );
	}

	/**
	 * Determine if the given membership can be deleted by the user.
	 */
	public function destroy( Model $user, Membership $membership): bool
	{
		if( $membership->getRole()->hasFlag( RoleFlag::Owner ) ){
			// Nobody can remove the owner for their own team.
			return false;
		}

		if( $membership->getTeam()->userCan( $user, 'membership.destroy' ) ){
			return true;
		}

		if( $membership->getMember()->is( $user ) ){
			// You can always remove yourself from a team.
			return true;
		}

		return false;
	}
}