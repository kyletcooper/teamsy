<?php

namespace WRD\Teamsy\Policies;

use Illuminate\Database\Eloquent\Model;
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
	 * Determine if the given membership can be deleted by the user.
	 */
	public function destroy( Model $user, Membership $membership): bool
	{
		if( $membership->getTeam()->userCan( $user, 'membership.destroy' ) ){
			return true;
		}

		if( $membership->getMember()->is( $user ) ){
			return true;
		}

		return false;
	}
}