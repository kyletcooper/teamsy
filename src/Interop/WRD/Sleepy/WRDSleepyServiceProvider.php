<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy;

use WRD\Teamsy\Interop\WRD\Sleepy\Generators\invitationGenerator;
use WRD\Teamsy\Interop\WRD\Sleepy\Generators\MembershipGenerator;
use WRD\Teamsy\Interop\WRD\Sleepy\Generators\RoleGenerator;
use WRD\Teamsy\Providers\InteropServiceProvider;

final class WRDSleepyServiceProvider extends InteropServiceProvider {
	static public array $require = [
		'wrd/sleepy'
	];

	public function register(): void {
		\WRD\Sleepy\Support\Facades\API::macro( 'memberships', function( string $teamType, ?string $path = null ){
			if( is_null( $path ) ){
				$path = "/{" . $teamType::getApiName() . "}/membership";
			}

			$generator = new MembershipGenerator( $teamType, $path );
			$generator->create();
		});

		\WRD\Sleepy\Support\Facades\API::macro( 'invitations', function( string $teamType, ?string $path = null ){
			if( is_null( $path ) ){
				$path = "/{" . $teamType::getApiName() . "}/invitation";
			}
			
			$generator = new invitationGenerator( $teamType, $path );
			$generator->create();
		});

		\WRD\Sleepy\Support\Facades\API::macro( 'roles', function( string $teamType, string $path = '/role' ){
			$generator = new RoleGenerator( $teamType, $path );
			$generator->create();
		});

		\WRD\Sleepy\Support\Facades\API::macro( 'teamsy', function( string $teamType ){
			\WRD\Sleepy\Support\Facades\API::memberships( $teamType );
			\WRD\Sleepy\Support\Facades\API::invitations( $teamType );
			\WRD\Sleepy\Support\Facades\API::roles( $teamType );
		});
	}
}