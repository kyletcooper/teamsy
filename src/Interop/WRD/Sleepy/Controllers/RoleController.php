<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy\Controllers;

use Illuminate\Database\Eloquent\Model;
use WRD\Sleepy\Http\Exceptions\ApiNotFoundException;
use WRD\Sleepy\Http\Requests\ApiRequest;
use WRD\Sleepy\Support\Facades\API;
use WRD\Teamsy\Interop\WRD\Sleepy\Layouts\Role;

class RoleController{
	public string $teamType;

	public function __construct( string $teamType )
	{
		$this->teamType = $teamType;
	}

	public function getTeam( ApiRequest $request ): Model{
		$value = $request->route()->parameter( $this->teamType::getApiName() );

		if( ! is_a( $value, $this->teamType ) ){
			abort( new ApiNotFoundException() );
		}

		return $value;
	}

	public function index( ApiRequest $request ){
		// 1. Get all roles for this team.
		$team = $this->getTeam( $request );
		$roles = $team->getRoles();

		// 2. Apply the Role layout to them.
		$layouts = $roles->map(fn( $role ) => (new Role)->present( $role ));

		// 3. Return the response.
		return API::response($layouts->all(), 200);
	}
}