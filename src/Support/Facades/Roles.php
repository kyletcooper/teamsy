<?php

namespace WRD\Teamsy\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \WRD\Teamsy\Capabilities\RoleRegistry
 */
class Roles extends Facade{
	protected static function getFacadeAccessor(){
		return 'teamsyRoles';
	}
}