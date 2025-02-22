<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy\Generators;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use WRD\Sleepy\Api\Generators\Generator;
use WRD\Sleepy\Support\Facades\API;
use WRD\Teamsy\Interop\WRD\Sleepy\Controllers\MembershipController;
use WRD\Teamsy\Traits\HasTeam;
use WRD\Sleepy\Fields\HasApiModel;
use WRD\Sleepy\Http\Middleware\SpecifiedBinding;
use WRD\Sleepy\Http\Requests\ApiRequest;
use WRD\Teamsy\Models\Membership;
use WRD\Teamsy\Support\Ensure;

class MembershipGenerator extends Generator {
	public string $path;
	public string $teamApiName;
	public MembershipController $controller;

	public function __construct( string $teamType, string $path )
	{
		Ensure::usesTrait( $teamType, HasTeam::class );
		Ensure::usesTrait( $teamType, HasApiModel::class );
		Ensure::usesTrait( config('teamsy.models.user'), HasApiModel::class );

		$this->path = $path;
		$this->teamApiName = $teamType::getApiName();
		$this->controller = new MembershipController( $teamType );
	}

	public function create(){
		// Collection
		API::route( $this->path, function() {

			API::endpoint( 'GET', [$this->controller, "index" ] )
				->auth( fn( ApiRequest $req ) => Gate::allows( 'viewAny', [Membership::class, $req->route()->parameter( $this->teamApiName )] ) )
				->responses( 200, 400, 401, 403 )
				->describe( 'View the memberships to this team.' );
		})
		->schema( $this->controller->getLayout()->schema() );

		// Self
		API::route( $this->path . '/{membershipish}', function() {

			API::endpoint( 'GET', [$this->controller, "show" ] )
			->auth( fn( ApiRequest $req ) => Gate::allows( 'view', $req->route()->parameter( 'membershipish' ) ) )
				->responses( 200, 401, 403 )
				->describe( 'View a membership in this team.' );
			
			API::endpoint( 'POST', [$this->controller, "update" ] )
				->auth( fn( ApiRequest $req ) => Gate::allows( 'update', $req->route()->parameter( 'membershipish' ) ) )
				->fields( $this->controller->getFields() )
				->responses( 200, 400, 401, 403 )
				->describe( 'Update a membership in this team.' );

			API::endpoint( 'DELETE', [$this->controller, "destroy" ] )
				->auth( fn( ApiRequest $req ) => Gate::allows( 'destroy', $req->route()->parameter( 'membershipish' ) ) )
				->responses( 204, 401, 403 )
				->describe( 'Remove a user from this team.' );
		})
		->middleware( SpecifiedBinding::class . ":membershipish," . Membership::class )
		->schema( $this->controller->getLayout()->schema() );
	}
}