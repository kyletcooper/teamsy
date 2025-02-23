<?php

namespace WRD\Teamsy\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use WRD\Teamsy\Events\JoiningTeam;
use WRD\Teamsy\Events\LeavingTeam;
use WRD\Teamsy\Events\RoleChanging;
use WRD\Teamsy\Capabilities\Role;
use WRD\Teamsy\Contracts\Membershipish;
use WRD\Teamsy\Support\Facades\Roles;

class Membership extends MorphPivot implements Membershipish{
	/**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'memberships';

	/**
	 * Get the team this membership applies to.
	 * 
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getTeam(): Model {
		return $this->team_type::findOrFail( $this->team_id );
	}

	/**
	 * Get the object class of the team.
	 * 
	 * @return string
	 */
	public function getTeamType(): string{
		return $this->team_type;
	}

	/**
	 * Get a Carbon instance for the datetime when the membership was created.
	 * 
	 * For the Membershipish contract.
	 * 
	 * @return Carbon
	 */
	public function getCreatedAt(): Carbon{
		return $this->{$this->getCreatedAtColumn()};
	}

	/**
	 * Get the user this membership applies to.
	 * 
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getMember(): Model{
		return config("teamsy.models.user")::findOrFail( $this->member_id );
	}

	/**
	 * Get the role of the user in this team.
	 * 
	 * If we can't get the role, then we default to the guest role.
	 * 
	 * @return Role
	 */
	public function getRole(): Role{
		return Roles::find( $this->role_id, $this->getTeamType() ) ?? Roles::guest();
	}

	/**
	 * Set the role of the user in this team.
	 * 
	 * @param string $role_id
	 * 
	 * @return void
	 */
	public function setRole(string $role_id): void{
		if( ! Roles::validate( $role_id, $this->getTeamType() ) ){
			throw new Exception( "Role does not exist." );
		}

		$this->role_id = $role_id;
		$this->save();
	}

	/**
	 * Revoke the membership.
	 * 
	 * @return void
	 */
	public function revoke(): void{
		$this->delete();
	}

	/**
	 * Boot the model.
	 * 
	 * @return void
	 */
	protected static function booted(): void{
		static::created(function( Membership $membership ){
			$team = $membership->getTeam();
			$user = $membership->getMember();
			$role = $membership->getRole();

			event( new JoiningTeam( $team, $user, $role ) );
		});

		static::updating(function( Membership $membership ){
			if( $membership->isDirty( 'role_id' ) ){
				$team = $membership->getTeam();
				$user = $membership->getMember();
				$after = $membership->getRole();
				
				$prevRoleId = $membership->getOriginal('role_id');
				$before = Roles::find( $prevRoleId, $this->getTeamType() ) ?? Roles::guest();
				
				event( new RoleChanging( $team, $user, $before, $after ) );
			}
		});

		static::deleting(function( Membership $membership ){
			$team = $membership->getTeam();
			$user = $membership->getMember();
			$role = $membership->getRole();

			event( new LeavingTeam( $team, $user, $role ) );
		});
	}
}