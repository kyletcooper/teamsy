<?php

namespace WRD\Teamsy\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use WRD\Teamsy\Capabilities\Role;
use WRD\Teamsy\Events\TeamCreated;
use WRD\Teamsy\Events\TeamDeleting;
use WRD\Teamsy\Models\Invitation;
use WRD\Teamsy\Models\Membership;
use WRD\Teamsy\Support\Facades\Roles;

trait HasTeam {
	/**
     * Get the entity's members.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Team, $this>
     */
    public function members()
    {
        return $this->morphToMany(config("teamsy.models.user"), 'team', 'memberships', null, 'member_id' )
			->withPivot('id', 'role_id')
			->withTimestamps()
			->as('membership')
			->using( Membership::class );
    }

	/**
	 * Get the membership of a user in this team.
	 * 
	 * @return \WRD\Teamsy\Models\Membership|null
	 */
	public function getMembershipOf( Model $user ): ?Membership{
		return $this->members()->where( 'member_id', $user->getKey() )->first()?->membership;
	}

	/**
	 * Get the user's role in this team.
	 * 
	 * @return \WRD\Teamsy\Capabilities\Role
	 */
	public function getRoleOf( Model $user ): Role{
		return $this->getMembershipOf( $user )?->getRole() ?? Roles::guest();
	}

	/**
	 * Check if a role can perform a capability in this team.
	 * 
	 * @return bool
	 */
	public function roleCan( string $role_id, string $capability ): bool{
		$role = Roles::find( $role_id, static::class ) ?? Roles::guest();

		return $role->hasCapability( $capability );
	}

	/**
	 * Check if a user can perform a capability in this team.
	 * 
	 * @return bool
	 */
	public function userCan( Model $user, string $capability ): bool{
		return $this->getRoleOf( $user )->hasCapability( $capability );
	}

	/**
	 * Check if the current user can perform a capability in this team.
	 * 
	 * @return bool
	 */
	public function currentUserCan( string $capability ): bool{
		return $this->getRoleOf( Auth::user() )->hasCapability( $capability );
	}

	/**
	 * Set the user's role in a team.
	 * 
	 * @return \WRD\Teamsy\Models\Role
	 */
	public function setRoleOf( Model $user, string $role ): void{
		$this->getMembershipOf( $user )?->setRole( $role );
	}

	/**
	 * Check if a user is in this team.
	 * 
	 * @return void
	 */
	public function hasMember( Model $user ): bool{
		return $this->members()->where( 'member_id', $user->getKey() )->exists();
	}

	/**
	 * Check if there is a user with a given email address in this team.
	 * 
	 * @return void
	 */
	public function hasMemberWithEmail( string $email ): bool{
		return $this->members()->where( 'email', $email )->exists();
	}

	/**
	 * Add a user to this team.
	 * 
	 * @return void
	 */
	public function addMember( Model $user, string $role_id ): void{
		if( ! Roles::validate( $role_id, $this->getTeamType() ) ){
			throw new Exception( "Role does not exist." );
		}

		$this->members()->attach( $user, ['role_id' => $role_id] );
	}

	/**
	 * Remove a user from this team.
	 * 
	 * @return void
	 */
	public function removeMember( Model $user ): void{
		$this->getMembershipOf( $user )->revoke();
	}

	/**
	 * Get the team type.
	 * 
	 * @return string
	 */
	public function getTeamType(): string{
		return get_class( $this );
	}

	/**
	 * Create a new role, scoped to this team type.
	 * 
	 * @return Role
	 */
	static public function registerRole( string $name, array $capabilities ): Role{
		return Roles::create( $name, $capabilities )->scope( static::class );
	}

	/**
	 * Get all of the roles applicable to this team type.
	 * 
	 * @return \Illuminate\Support\Collection<Role>
	 */
	static public function getRoles(): Collection{
		return Roles::scope( static::class );
	}

	/**
     * Get the entity's invitations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Invitation, $this>
     */
    public function invitations()
    {
        return $this->morphMany(Invitation::class, 'team')->latest();
    }

	/**
	 * Invite a user to this team.
	 * 
	 * @return \WRD\Teamsy\Models\Invitation
	 */
	public function inviteUser( Model $user, string $role_id, ?Model $invitor = null, ?string $message = null ): Invitation{
		return Invitation::createForUser( $this, $user, $role_id, $invitor, $message );
	}

	/**
	 * Boot the trait.
	 * 
	 * @return void
	 */
	protected static function bootedHasTeam(): void{
		static::created(function( Model $team ){
			event( new TeamCreated( $team ) );
		});

		static::deleting(function( Model $team ){
			event( new TeamDeleting( $team ) );
		});
	}
}
