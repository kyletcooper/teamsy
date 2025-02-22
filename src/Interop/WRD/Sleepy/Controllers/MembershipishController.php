<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use WRD\Sleepy\Http\Exceptions\ApiNotFoundException;
use WRD\Sleepy\Layouts\Layout;
use WRD\Sleepy\Support\Facades\API;

abstract class MembershipishController{
	/**
	 * The team class.
	 */
	public string $teamType;

	/**
	 * Create the controller
	 */
	public function __construct( string $teamType )
	{
		$this->teamType = $teamType;
	}

	/**
	 * Get the team in this request.
	 * 
	 * @return Model
	 */
	public function getTeam(): Model{
		$value = request()->route()->parameter( $this->teamType::getApiName() );

		if( ! is_a( $value, $this->teamType ) ){
			abort( new ApiNotFoundException() );
		}

		return $value;
	}

	/**
	 * Get the team in this request.
	 * 
	 * @return \WRD\Teamsy\Contracts\Membershipish|Model
	 */
	public function getModel(): Model{
		$value = request()->route()->parameter( "membershipish" );

		if( ! is_a( $value, $this->getMembershipish() ) ){
			abort( new ApiNotFoundException() );
		}
		
		if( ! $value->getTeam()->is( $this->getTeam() ) ){
			// The team and this membershipish are not related.
			// Someone is being naughty and trying to request a model from another team.
			abort( new ApiNotFoundException() );
		}

		return $value;
	}

	/**
	 * Format a collection response.
	 * 
	 * @param Collection
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function collection( Collection $collection ){
		$layouts = $collection
			->map(fn( $member ) => $this->getLayout()->present( $member ));

		return API::response($layouts->all(), 200);
	}

	/**
	 * Format a single response.
	 * 
	 * @param Model
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function single( Model $single ){
		$layouted = $this->getLayout()->present( $single );

		return API::response($layouted, 200);
	}

	abstract public function getMembershipish(): string;

	abstract public function getLayout(): Layout;

	abstract public function getFields(): array;
}