<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy\Generators;

use Illuminate\Support\Facades\Gate;
use WRD\Sleepy\Api\Generators\Generator;
use WRD\Sleepy\Fields\Field;
use WRD\Sleepy\Support\Facades\API;
use WRD\Teamsy\Interop\WRD\Sleepy\Controllers\InvitationController;
use WRD\Teamsy\Traits\HasTeam;
use WRD\Sleepy\Fields\HasApiModel;
use WRD\Sleepy\Http\Middleware\SpecifiedBinding;
use WRD\Sleepy\Http\Requests\ApiRequest;
use WRD\Teamsy\Models\Invitation;
use WRD\Teamsy\Support\Ensure;

class invitationGenerator extends Generator {
	public string $path;
	public string $teamApiName;
	public InvitationController $controller;

	public function __construct( string $teamType, string $path )
	{
		Ensure::usesTrait( $teamType, HasTeam::class );
		Ensure::usesTrait( $teamType, HasApiModel::class );
		Ensure::usesTrait( config('teamsy.models.user'), HasApiModel::class );

		$this->path = $path;
		$this->teamApiName = $teamType::getApiName();
		$this->controller = new InvitationController( $teamType );
	}

	public function create(){
		// Collection
		API::route( $this->path, function() {

			API::endpoint( 'GET', [$this->controller, "index" ] )
				->auth( fn( ApiRequest $req ) => Gate::allows( 'viewAny', [Invitation::class, $req->route()->parameter( $this->teamApiName )] ) )
				->responses( 200, 400, 401, 403 )
				->describe( 'View the invitations to this team.' );

			API::endpoint( 'POST', [$this->controller, "create" ] )
				->auth( fn( ApiRequest $req ) => Gate::allows( 'create', [Invitation::class, $req->route()->parameter( $this->teamApiName )] ) )
				->fields( $this->controller->getFields() )
				->responses( 200, 400, 401, 403 )
				->describe( 'Create a invitation to this team.' );
		})
		->schema( $this->controller->getLayout()->schema() );

		// Self
		API::route( $this->path . '/{membershipish}', function() {
			API::endpoint( 'GET', [$this->controller, "show" ] )
				->auth( fn( ApiRequest $req ) => Gate::allows( 'view', $req->route()->parameter( 'membershipish' ) ) )
				->responses( 200, 400, 401, 403 )
				->describe( 'View an invitation to this team.' );

			API::endpoint( 'DELETE', [$this->controller, "destroy" ] )
				->auth( fn( ApiRequest $req ) => Gate::allows( 'destroy', $req->route()->parameter( 'membershipish' ) ) )
				->responses( 204, 401, 403 )
				->describe( 'Revoke an invitation to this team.' );
		})
		->middleware( SpecifiedBinding::class . ":membershipish," . Invitation::class )
		->schema( $this->controller->getLayout()->schema() );

		// Respond
		API::route( $this->path . '/{membershipish}/respond', function() {
			API::endpoint( 'POST', [$this->controller, "respond" ] )
				->fields([
					'status' => Field::string()->enum(['accept', 'decline']),
				])
				->auth( fn( ApiRequest $req ) => Gate::allows( 'respond', $req->route()->parameter( 'membershipish' ) ) )
				->responses( 204, 400, 401, 403 )
				->describe( 'Respond to an invitation in this team.' );
		})
		->middleware( SpecifiedBinding::class . ":membershipish," . Invitation::class );
	}
}