<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy\Controllers;

use WRD\Teamsy\Rules\ValidRole;
use Illuminate\Support\Facades\Auth;
use WRD\Sleepy\Fields\Field;
use WRD\Sleepy\Http\Requests\ApiRequest;
use WRD\Sleepy\Layouts\Layout;
use WRD\Sleepy\Support\Facades\API;
use WRD\Teamsy\Interop\WRD\Sleepy\Layouts\Invitation;
use WRD\Teamsy\Models\Invitation as ModelsInvitation;
use WRD\Teamsy\Rules\CanInvite;

class InvitationController extends MembershipishController{
	/**
	 * Get the layout for this controller.
	 * 
	 * @return Layout
	 */
	public function getLayout(): Layout{
		return new Invitation();
	}

	/**
	 * Get the type of membershipish model name for this controller.
	 * 
	 * @return string
	 */
	public function getMembershipish(): string{
		return ModelsInvitation::class;
	}

	/**
	 * Get the fields available when updating a membership.
	 * 
	 * @return array
	 */
	public function getFields(): array{
		return [
			'email' => Field::string( 'email' )
				->custom([ new CanInvite( [$this, 'getTeam'] ) ])
				->required(),
			'role' => Field::string()
				->custom([ new ValidRole( $this->teamType ) ])
				->required(),
			'message' => Field::string()
				->optional()
		];
	}

	/**
	 * Get all invitations to join a team
	 */
	public function index(){
		$team = $this->getTeam();
		$invites = $team->invitations;

		return $this->collection( $invites );
	}

	/**
	 * Create a new invitation.
	 */
	public function create( ApiRequest $request ){
		$team    = $this->getTeam();
		$email   = $request->values()->get( "email" );
		$role    = $request->values()->get( "role" );
		$sender  = Auth::user();
		$message = $request->values()->get( "message" );

		$invite = ModelsInvitation::createForEmail( $team, $email, $role, $sender, $message );

		return $this->single( $invite );
	}

	/**
	 * Show a single invitation to a team.
	 */
	public function show(){
		$invite = $this->getModel();

		return $this->single( $invite );
	}

	/**
	 * Revoke an invitation.
	 */
	public function destroy(){
		$invitation = $this->getModel();

		$invitation->revoke();

		return API::response( null, 204 );
	}

	/**
	 * Accept or decline an invitation.
	 */
	public function respond( ApiRequest $request ){
		$invitation = $this->getModel();
		$status = $request->values()->get( 'status' );

		if( $status === "accept" ){
			$invitation->accept();
		}
		else if( $status === "decline" ){
			$invitation->decline();
		}

		return API::response( null, 204 );
	}
}