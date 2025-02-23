<?php

namespace WRD\Teamsy\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use WRD\Teamsy\Capabilities\Role;
use WRD\Teamsy\Contracts\Membershipish;
use WRD\Teamsy\Enums\InvitationStatus;
use WRD\Teamsy\Events\InviteAccepted;
use WRD\Teamsy\Events\InviteCreated;
use WRD\Teamsy\Events\InviteDeclined;
use WRD\Teamsy\Events\InviteRevoked;
use WRD\Teamsy\Support\Ensure;
use WRD\Teamsy\Support\Facades\Roles;

class Invitation extends Model implements Membershipish{
    use SoftDeletes {
        forceDelete as protected _forceDelete;
    }

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
    protected $table = 'invitations';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

	/**
     * Get the invitation's invitee (if they exist yet).
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getInvitee(): ?Model {
        return config("teamsy.models.user")::where( 'email', $this->email )->first();
    }

    /**
     * Get the invitation's sender (if set).
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getSender(): ?Model {
        return $this->sender;
    }

    /**
     * Get the invitation's sender.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<User, $this>
     */
    public function sender(): BelongsTo{
        return $this->belongsTo(config("teamsy.models.user"), 'creator_id');
    }

	/**
     * Get the invitation's team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Team, $this>
     */
    public function team() {
        return $this->morphTo("invitations", "team_type", "team_id");
    }

    /**
	 * Get the status of this invitation.
	 * 
	 * @return InvitationStatus
	 */
	public function getStatus(): InvitationStatus{
        if( $this->isDeclined() ){
            return InvitationStatus::Declined;
        }

		return InvitationStatus::Pending;
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
     * Alias for GetInvitee. Used for Membershipish contract.
     * 
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getMember(): ?Model {
        return $this->getInvitee();
    }

    /**
     * Alias for team. Used for Membershipish contract.
     * 
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getTeam(): ?Model {
        return $this->team()->first();
    }

    /**
	 * Get the object class of the team.
	 * 
	 * @return string
	 */
    public function getTeamType(): string{
        return get_class( $this->getTeam() );
    }

    /**
	 * Get the role the user will have in the team, if they accept.
	 * 
	 * If we can't get the role, then we default to the guest role.
	 * 
	 * @return Role
	 */
	public function getRole(): Role{
		return Roles::find( $this->role_id, $this->getTeamType() ) ?? Roles::guest();
	}

    /**
     * Check if an invitation has been declined.
     * 
     * @return bool
     */
    public function isDeclined(): bool{
        return $this->trashed();
    }

    /**
     * Override the model's delete function to give a warning if called incorrectly.
     * 
     * Callers should use the revoke, accept or decline methods and not call delete directly.
     * 
     * @return ?bool
     */
    public function delete(): ?bool{
        Ensure::callerIn([ 'decline', '_forceDelete' ]);

        return parent::delete();
    }

    /**
     * Override the model's force delete function to give a warning if called incorrectly.
     * 
     * Callers should use the revoke, accept or decline methods and not call delete directly.
     * 
     * @return ?bool
     */
    public function forceDelete(): ?bool{
        Ensure::callerIn([ 'accept', 'revoke' ]);

        return $this->_forceDelete();
    }

    /**
     * Revoke an invitation so the invitee cannot accept it.
     * 
     * @return void
     */
    public function revoke(): void{
        event( new InviteRevoked( $this ) );

        $this->forceDelete();
    }

    /**
     * Accept an invitation and join the team.
     * 
     * @return void
     */
    public function accept(): void{
        $user = $this->getInvitee();
        $team = $this->team;

        if( is_null( $user ) || is_null( $team ) ){
            throw new Exception("Cannot accept invitation when there is no user matching the email address or the team does not exist.");
        }

        if( $this->isDeclined() ){
            throw new Exception("Cannot accept a trashed invitation.");
        }

        $team->addMember( $user, $this->role_id );

        event( new InviteAccepted( $this ) );

        $this->forceDelete();
    }

    /**
     * Decline an invitation, such that it cannot be sent again.
     * 
     * @return void
     */
    public function decline(): void{
        event( new InviteDeclined( $this ) );

        $this->delete();
    }

    /**
     * Invite a user to join a team.
     * 
     * @return \WRD\Teamsy\Models\Invitation
     */
    static public function createForUser( Model $team, Model $invitee, string $role_id, Model $sender = null, string $message = null ): static{
        if( $team->hasMember( $invitee ) ){
            throw new Exception( "User is already in team." );
        }

        return static::createForEmail( $team, $invitee->email, $role_id, $sender, $message );
    }

    /**
     * Checks if there is an invitation for an email address in a team.
     * 
     * @return bool
     */
    static public function existsForEmail( Model $team, string $email ): bool{
        return static::withTrashed() // Include invitations the user has denied.
            ->where( 'email', $email )
            ->where( 'team_id', $team->getKey() )
            ->where( 'team_type', $team::class )
            ->exists();
    }

    /**
     * Invite a new user by their email address to join a team.
     * 
     * @return \WRD\Teamsy\Models\Invitation
     */
    static public function createForEmail( Model $team, string $email, string $role_id, Model $sender = null, string $message = null ): static{
        if( static::existsForEmail( $team, $email ) ){
            throw new Exception( "Invitation already exists." );
        }

        if( $team->hasMemberWithEmail( $email ) ){
            throw new Exception( "User already in team." );
        }

        return static::create([
            'message' => $message,
            'creator_id' => $sender?->getKey(),
            'email' => $email,
            'role_id' => $role_id,
            'team_id' => $team->getKey(),
            'team_type' => $team::class,
        ]);
    }

    /**
	 * Boot the model.
	 * 
	 * @return void
	 */
	protected static function booted(): void{
		static::created(function( Invitation $invitation ){
			event( new InviteCreated( $invitation ) );
		});
    }
}