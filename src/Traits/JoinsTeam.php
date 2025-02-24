<?php

namespace WRD\Teamsy\Traits;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use WRD\Teamsy\Attributes\Team;
use WRD\Teamsy\Capabilities\Role;
use WRD\Teamsy\Events\MemberCreated;
use WRD\Teamsy\Events\MemberDeleting;
use WRD\Teamsy\Models\Invitation;
use WRD\Teamsy\Models\Membership;
use WRD\Teamsy\Models\Relations\HasTeam;
use WRD\Teamsy\Support\Facades\Roles;

trait JoinsTeam {
	/**
     * Create a relationship between the user an a team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasTeam<Team, $this>
     */
	public function hasTeam( string $team_class ): HasTeam{
		$related = $team_class;
		$name = 'team';
		$table = 'memberships';
		$foreignPivotKey = 'member_id';
		$relatedPivotKey = $name.'_id';
		
		$instance = $this->newRelatedInstance( $related );
		$relatedKey = $instance->getKeyName();
		$query = $instance->newQuery();
		
		$parent = $this;
		$parentKey = $this->getKeyName();

		$relationName = $this->guessBelongsToManyRelation();
		$inverse = true;

		$relationship = new HasTeam($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName, $inverse);

		return $relationship
			->withPivot('id', 'role_id')
			->withTimestamps()
			->as('membership')
			->using( Membership::class );
	}

	/**
     * Query the relationship for a user's pending invitations.
	 * 
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function pendingInvitations(): Builder {
        return Invitation::where( 'email', $this->email );
    }

	/**
     * Query the relationship for a user's declined invitations.
	 * 
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function getDeclinedInvitations(): Builder {
        return Invitation::onlyTrashed()->where( 'email', $this->email );
    }

	/**
     * Get the relationship for a user's sent invitations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Team, $this>
     */
    public function sentInvitations() {
		return $this->hasMany( Invitation::class, 'creator_id' );
    }

	/**
	 * Guess the name of a relationship
	 * 
	 * @param string $class The classname of the foreign model.
	 * 
	 * @return string
	 */
	private function guessTeamRelationshipName( string $class ): string {
		return Str::of( $class )->basename()->plural()->camel()->__toString();
	}

	/**
	 * Get the user's membership to a team.
	 * 
	 * @return \WRD\Teamsy\Models\Membership|null
	 */
	public function getMembershipIn( Model $team, string $relationship = null ): ?Membership{
		$relationship = $relationship ?: $this->guessTeamRelationshipName( $team::class );

		return $this->{$relationship}()->where( 'team_id', $team->getKey() )->first()?->membership;
	}

	/**
	 * Get the user's role in a team.
	 * 
	 * @return \WRD\Teamsy\Capabilities\Role
	 */
	public function getRoleIn( Model $team, string $relationship = null ): Role{
		return $this->getMembershipIn( $team, $relationship )?->getRole() ?? Roles::guest();
	}

	/**
	 * Check if the user can perform a capability in a team.
	 * 
	 * @return bool
	 */
	public function canIn( Model $team, string $capability, string $relationship = null ): bool{
		return $this->getRoleIn( $team, $relationship )->hasCapability( $capability );
	}

	/**
	 * Set the user's role in a team.
	 * 
	 * @return \WRD\Teamsy\Models\Role
	 */
	public function setRoleIn( Model $team, string $role, string $relationship = null ): void{
		$this->getMembershipIn( $team, $relationship )?->setRole( $role );
	}

	/**
	 * Check if the user is in a team.
	 * 
	 * @return void
	 */
	public function inTeam( Model $team ): bool{
		return $team->hasMember( $this );
	}

	/**
	 * Add the user to a team.
	 * 
	 * @return void
	 */
	public function joinTeam( Model $team, string $role_id ): void{
		$team->addMember( $this, $role_id );
	}

	/**
	 * Invite the user to a team.
	 * 
	 * @return \WRD\Teamsy\Models\Invitation
	 */
	public function inviteToTeam( Model $team, string $role_id, ?Model $invitor = null, ?string $message = null ): Invitation{
		return Invitation::createForUser( $team, $this, $role_id, $invitor, $message );
	}

	/**
	 * Remove the user from a team.
	 * 
	 * @return void
	 */
	public function leaveTeam( Model $team, string $relationship = null ): void{
		$this->getMembershipIn( $team, $relationship )->revoke();
	}

	/**
	 * Boot the trait.
	 * 
	 * @return void
	 */
	protected static function bootedHasTeam(): void{
		static::created(function( Model $user ){
			event( new MemberCreated( $user ) );
		});

		static::deleting(function( Model $user ){
			event( new MemberDeleting( $user ) );
		});
	}
}