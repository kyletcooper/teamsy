<?php

namespace WRD\Teamsy\Capabilities;

use Illuminate\Support\Collection;
use WRD\Teamsy\Support\Registry;

/**
 * @implements \WRD\Teamsy\Support\Registry<\WRD\Teamsy\Capabilities\Role>
 */
class RolesRegistry extends Registry{
	public function __construct()
	{
		parent::__construct();

		$this->register( 'owner', static::owner() );
		$this->register( 'guest', static::guest() );
	}

	/**
	 * Create a new role and add it to the registry.
	 * 
	 * @param string $id
	 * 
	 * @param array $capabilities
	 * 
	 * @return \WRD\Teamsy\Capabilities\Role
	 */
	public function create( string $id, array $capabilities = [] ): Role{
		$role = new Role( $id, $capabilities );

		$this->register( $id, $role );

		return $role;
	}

	/**
	 * Find a role.
	 * 
	 * @param string $id
	 * 
	 * @param ?string $teamType
	 * 
	 * @return ?\WRD\Teamsy\Capabilities\Role
	 */
	public function find( string $id, ?string $teamType = null ): ?Role{
		return $this->items->first( fn( Role $role ) =>
			$role->getId() === $id && $role->scopedFor( $teamType )
		);
	}

	/**
	 * Get all roles applicable to a team type.
	 * 
	 * @param string $teamType
	 * 
	 * @return \Illuminate\Support\Collection<Role>
	 */
	public function scope( string $teamType = null ): Collection{
		return $this->items->filter( fn( Role $role ) =>
			$role->scopedFor( $teamType )
		);
	}

	/**
	 * Checks if a role exists and can be assigned.
	 * 
	 * @param string $id
	 * 
	 * @param string $teamType
	 * 
	 * @return bool
	 */
	public function validate( string $id, string $teamType ){
		return ! is_null( $this->find( $id, $teamType ) );
	}

	/**
	 * Get the owner role.
	 * 
	 * @return Role
	 */
	public function owner(): Role{
		return (new Role( "owner" ))
			->allow('*')
			->title('Owner')
			->describe("The user who created the team.")
			->flags([RoleFlag::Owner]);
	}

	/**
	 * Get the guest role.
	 * 
	 * @return Role
	 */
	public function guest(): Role{
		return (new Role( "guest" ))
			->allow('exist')
			->title('Guest')
			->describe("Role for users who are not logged in. Also used as a fallback if the user's role in the database cannot be found.")
			->flags([RoleFlag::Guest]);
	}
}