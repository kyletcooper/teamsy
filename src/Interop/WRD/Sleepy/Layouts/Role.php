<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy\Layouts;

use Illuminate\Support\Str;
use WRD\Sleepy\Layouts\Layout;
use WRD\Sleepy\Schema\Schema;
use WRD\Teamsy\Capabilities\RoleFlag;

class Role extends Layout{
	public function schema(): Schema{
		return Schema::object([
			'id' => Schema::string(),
			'scope' => Schema::string(),
			'title' => Schema::string(),
			'description' => Schema::string(),
			'capabilities' => Schema::array( Schema::string() ),
			'flags' => Schema::array( Schema::string()->enum( RoleFlag::cases() ) )
		]);
	}

	/**
	 * Present the value.
	 * 
	 * @param \WRD\Teamsy\Capabilities\Role $role
	 */
	public function present( $role ): array{
		$scope = Str::of( $role->getScope() )->basename()->lower()->toString();

		return [
			'id' => $role->getId(),
			'scope' => $scope,
			'title' => $role->getTitle(),
			'description' => $role->getDescription(),
			'capabilities' => $role->getCapabilities(),
			'flags' => $role->getFlags(),
		];
	}
}