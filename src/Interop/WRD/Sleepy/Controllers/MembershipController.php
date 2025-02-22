<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy\Controllers;

use WRD\Teamsy\Rules\ValidRole;
use WRD\Sleepy\Fields\Field;
use WRD\Sleepy\Http\Requests\ApiRequest;
use WRD\Sleepy\Layouts\Layout;
use WRD\Sleepy\Support\Facades\API;
use WRD\Teamsy\Interop\WRD\Sleepy\Layouts\Membership;
use WRD\Teamsy\Models\Membership as ModelsMembership;

class MembershipController extends MembershipishController{
	/**
	 * Get the layout for this controller.
	 * 
	 * @return Layout
	 */
	public function getLayout(): Layout{
		return new Membership();
	}

	/**
	 * Get the type of membershipish model name for this controller.
	 * 
	 * @return string
	 */
	public function getMembershipish(): string{
		return ModelsMembership::class;
	}

	/**
	 * Get the fields available when updating a membership.
	 * 
	 * @return array
	 */
	public function getFields(): array{
		return [
			'role' => Field::string()
				->custom([ new ValidRole( $this->teamType ) ])
				->required(),
		];
	}

	/**
	 * List all memberships in a team.
	 */
	public function index(){
		$team = $this->getTeam();
		$memberships = $team->members->map(fn($user) => $user->membership);

		return $this->collection( $memberships );
	}

	/**
	 * Show a single membership in a team.
	 */
	public function show(){
		$membership = $this->getModel();

		return $this->single( $membership );
	}

	/**
	 * Update a membership
	 */
	public function update( ApiRequest $request ){
		$membership = $this->getModel( $request );

		$membership->role_id = $request->values()->get( "role" );
		$membership->save();

		return $this->single( $membership );
	}

	/**
	 * Revoke a membership.
	 */
	public function destroy(){
		$membership = $this->getModel();

		$membership->revoke();

		return API::response( null, 204 );
	}
}