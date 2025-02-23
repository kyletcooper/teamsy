<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy\Generators;

use Exception;
use Illuminate\Support\Facades\Gate;
use WRD\Sleepy\Api\Generators\Generator;
use WRD\Sleepy\Http\Requests\ApiRequest;
use WRD\Sleepy\Layouts\Layout;
use WRD\Sleepy\Support\Facades\API;
use WRD\Teamsy\Traits\HasTeam;
use WRD\Teamsy\Interop\WRD\Sleepy\Controllers\RoleController;
use WRD\Teamsy\Interop\WRD\Sleepy\Layouts\Role;
use WRD\Teamsy\Models\Membership;

class RoleGenerator extends Generator {
	public string $path;
	public string $teamApiName;
	public RoleController $controller;

	public function __construct( string $teamType, string $path )
	{
		if( ! in_array( HasTeam::class, class_uses_recursive( $teamType ) ) ){
			throw new Exception( "Cannot create roles route for model $teamType, it does not use the HasTeam trait." );
		}

		$this->teamApiName = $teamType::getApiName();
		$this->path = "/{" . $teamType::getApiName() . "}" . $path;
		$this->controller = new RoleController( $teamType );
	}

	public function create(){
		API::route( $this->path, function(){
			API::endpoint( 'GET', [$this->controller, "index" ] )
				->auth( fn( ApiRequest $req ) => Gate::allows( 'viewAny', [Membership::class, $req->route()->parameter( $this->teamApiName )] ) )
				->responses( 200, 400, 401, 403 )
				->describe( 'Get the available roles for this team.' );
		})
		->schema( (new Role)->schema() );
	}
}