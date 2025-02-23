# Teamsy

A library for building nested, team-based permissions systems for Laravel.

Teamsy allows you to convert any model into a team with it's flexible membership system. Each user can join multiple teams with a role defining their capabilities.

> :warning: This package is under active development.

**Contents**
[Installation](#installation)
[Setup](#setup)
[Roles](#roles)
[Traits](#traits)
[Invitations](#invitations)
[Policies](#policies)
[Sleepy Integration](#sleepy-integration)

## Installation

You can install Teamsy using Composer:

```
composer required wrd/teamsy
```

And then publish the configuration file and migrations with the following command:

```
php artisan vendor:publish --tag=teamsy-install
```

## Setup

You just need to add the `JoinsTeam` trait to your user model and the `HasTeam` trait to your team models.

Teamsy will automatically create a relationship from your team model to your user, called `members`. On your team model, make sure to create the inverse relationship. You can use the `joinsTeam` function to build this relationship.

To aid in determining which of your user model's attributes are for teams, you should use the `#[Team()]` annotation on team relation functions. See the example below.

> Whilst the Team attribute is optional, it allows Teamsy to get a list of all your model's teams using in the `getAllTeamRelationships` method. We use this to automatically remove memberships & sent invitations when a user is deleted.

```php
use WRD\Teamsy\Attributes\Team;
use WRD\Teamsy\Traits\JoinsTeam;

class User{
	use JoinsTeam;

	#[Team()]
    public function organisations(): MorphToMany{
		return $this->joinsTeam(Organisation::class);
    }
}
```

## Roles

Roles are defined in the application and are not stored in the database. They are referenced by their ID. If the role ID saved in the database is no longer available in the application logic then Teamsy will default to a Guest role, with only one capability - `exist`.

Define roles in your service provider, like so. You can optionally scope roles to only exist within certain types (models) of teams.

You should always define a list of allowed capabilities for the role. The title and description are optional.

```php
/**
 * Bootstrap any application services.
 */
public function boot(): void
{
	Roles::create('admin')
		->allow([ '*' ])
		->title( 'Administrator' )
		->describe( 'I can do whatever I want, wherever I want.' );

	Roles::create('client')
		->scope( Organisation::class )
		->allow([
			'task.create',
			'task.update',
			'task.delete',
			'post.*',
		])
		->title( 'I can create, update and delete tasks in an organisation and do anything to posts.' );
}
```

You can define capabilities however you'd like. Teamsy will understand wildcard capabilities as allowing all nested capabilities using dot notation.

Capabilities are linked only to the team, not to any model they are being performed on. If you want to provide more complex permissions we'd recommend using your model's policy.

```php
class PostPolicy{
	public function view( User $user, Post $post ){
		if( $post->organisation->userCan( $user, 'post.view' ) ){
			// If the user can view posts in the organisation.
			return true;
		}

		if( $post->creator->is( $user ) ){
			// Or if they created the post themselves.
			return true;
		}

		return false;
	}
}
```

## Traits

You must add the `WRD\Teamsy\Trait\JoinsTeams` to your user model. You should also make sure that the user model is correctly assigned in the config (this defaults to `\App\Models\User`).

Any model can be converted to a team using the `WRD\Teamsy\Trait\HasTeam` trait. You can have as many or as few teams models as you'd like, allowing you to easily nest teams. However, it's important to note that capabilities/roles are not inherited between nested teams.

```php
trait JoinsTeam{
	/**
     * Create a relationship between the user an a team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany<Team, $this>
     */
	public function joinsTeam( string $team_class );

	/**
     * Query the relationship for a user's pending invitations.
	 *
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function pendingInvitations();

	/**
     * Query the relationship for a user's declined invitations.
	 *
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function getDeclinedInvitations();

	/**
     * Get the relationship for a user's sent invitations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Team, $this>
     */
    public function sentInvitations();

	/**
	 * Get the user's membership to a team.
	 *
	 * @return \WRD\Teamsy\Models\Membership|null
	 */
	public function getMembershipIn( Model $team, string $relationship = null );

	/**
	 * Get the user's role in a team.
	 *
	 * @return \WRD\Teamsy\Capabilities\Role
	 */
	public function getRoleIn( Model $team, string $relationship = null );

	/**
	 * Check if the user can perform a capability in a team.
	 *
	 * @return bool
	 */
	public function canIn( Model $team, string $capability, string $relationship = null );

	/**
	 * Set the user's role in a team.
	 *
	 * @return \WRD\Teamsy\Models\Role
	 */
	public function setRoleIn( Model $team, string $role, string $relationship = null );

	/**
	 * Check if the user is in a team.
	 *
	 * @return void
	 */
	public function inTeam( Model $team );

	/**
	 * Add the user to a team.
	 *
	 * @return void
	 */
	public function joinTeam( Model $team, string $role_id );

	/**
	 * Invite the user to a team.
	 *
	 * @return \WRD\Teamsy\Models\Invitation
	 */
	public function inviteToTeam( Model $team, string $role_id, ?Model $invitor = null, ?string $message = null );

	/**
	 * Remove the user from a team.
	 *
	 * @return void
	 */
	public function leaveTeam( Model $team, string $relationship = null );

	/**
	 * Get all of this model's relationships that are a team relationship.
	 *
	 * You should denote team relations with the #[Team()] annotation.
	 *
	 * @return string[]
	 */
	public function getAllTeamRelationships();
}
```

```php
trait HasTeam{
	/**
     * Get the entity's members.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Team, $this>
     */
    public function members();

	/**
	 * Get the membership of a user in this team.
	 *
	 * @return \WRD\Teamsy\Models\Membership|null
	 */
	public function getMembershipOf( Model $user );

	/**
	 * Get the user's role in this team.
	 *
	 * @return \WRD\Teamsy\Capabilities\Role
	 */
	public function getRoleOf( Model $user );

	/**
	 * Check if a user can perform a capability in this team.
	 *
	 * @return bool
	 */
	public function userCan( Model $user, string $capability );

	/**
	 * Check if the current user can perform a capability in this team.
	 *
	 * @return bool
	 */
	public function currentUserCan( string $capability );

	/**
	 * Set the user's role in a team.
	 *
	 * @return \WRD\Teamsy\Models\Role
	 */
	public function setRoleOf( Model $user, string $role );

	/**
	 * Check if a user is in this team.
	 *
	 * @return void
	 */
	public function hasMember( Model $user );

	/**
	 * Check if there is a user with a given email address in this team.
	 *
	 * @return void
	 */
	public function hasMemberWithEmail( string $email );

	/**
	 * Add a user to this team.
	 *
	 * @return void
	 */
	public function addMember( Model $user, string $role_id );

	/**
	 * Remove a user from this team.
	 *
	 * @return void
	 */
	public function removeMember( Model $user );

	/**
	 * Create a new role, scoped to this team type.
	 *
	 * @return Role
	 */
	static public function registerRole( string $name, array $capabilities );

	/**
	 * Get all of the roles applicable to this team type.
	 *
	 * @return \Illuminate\Support\Collection<Role>
	 */
	static public function getRoles();

	/**
     * Get the entity's invitations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Invitation, $this>
     */
    public function invitations();

	/**
	 * Invite a user to this team.
	 *
	 * @return \WRD\Teamsy\Models\Invitation
	 */
	public function inviteUser( Model $user, string $role_id, ?Model $invitor = null, ?string $message = null );
}

```

## Invitations

Teamsy includes an invitation system out of the box. Invitations are attached to an email address so that you are able to invite users who don't have an account registered yet. Invitation have the role set and, when accepted, add the user to the team with said role.

- Invitations must be unique per email per team.
- Initation cannot be created if there is a user with the email address already in the team.
- Invitations can have a sender attached.
- Invitations can have a custom message.
- Invitations are automatically deleted if the team is deleted, the sender is deleted or the sender is demoted to a role without permission to create invitations.
- Users can choose to reject an invitation, soft-deleting it, so that they are no re-invited.

To customise invitation notifications public the invitation notification with the following command:

```
php artisan vendor:publish --tag=teamsy-notifications
```

You could also update the invitation model in the Teamsy config.

> :warning: Invitations are not revoked when a user is removed from a team. This is something we aim to fix in the future.

```php
class Invitation extends Model implements Membershipish{
	/**
     * Get the invitation's invitee (if they exist yet).
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getInvitee();

    /**
     * Get the invitation's sender (if set).
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getSender();

    /**
     * Get the invitation's sender.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<User, $this>
     */
    public function sender();

	/**
     * Get the invitation's team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Team, $this>
     */
    public function team();

    /**
	 * Get the status of this invitation.
	 *
	 * @return InvitationStatus
	 */
	public function getStatus();

    /**
	 * Get a Carbon instance for the datetime when the membership was created.
	 *
	 * For the Membershipish contract.
	 *
	 * @return Carbon
	 */
	public function getCreatedAt();

    /**
     * Alias for GetInvitee. Used for Membershipish contract.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getMember();

    /**
     * Alias for team. Used for Membershipish contract.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getTeam();

    /**
	 * Get the object class of the team.
	 *
	 * @return string
	 */
    public function getTeamType();

    /**
	 * Get the role the user will have in the team, if they accept.
	 *
	 * If we can't get the role, then we default to the guest role.
	 *
	 * @return Role
	 */
	public function getRole();

    /**
     * Check if an invitation has been declined.
     *
     * @return bool
     */
    public function isDeclined();

    /**
     * Revoke an invitation so the invitee cannot accept it.
     *
     * @return void
     */
    public function revoke();

    /**
     * Accept an invitation and join the team.
     *
     * @return void
     */
    public function accept();

    /**
     * Decline an invitation, such that it cannot be sent again.
     *
     * @return void
     */
    public function decline();

    /**
     * Invite a user to join a team.
     *
     * @return \WRD\Teamsy\Models\Invitation
     */
    static public function createForUser( Model $team, Model $invitee, string $role_id, Model $sender = null, string $message = null );

    /**
     * Checks if there is an invitation for an email address in a team.
     *
     * @return bool
     */
    static public function existsForEmail( Model $team, string $email );

    /**
     * Invite a new user by their email address to join a team.
     *
     * @return \WRD\Teamsy\Models\Invitation
     */
    static public function createForEmail( Model $team, string $email, string $role_id, Model $sender = null, string $message = null );
}
```

## Policies

To protect the Membership & Invitation models, Teamsy provides default policies. These defer to their attached team to query to following capabilities.

| Capability         | Description                       |
| ------------------ | --------------------------------- |
| invitation.viewAny | Can view all invites to the team. |
| invitation.view    | View invitations to the team.     |
| invitation.create  | Invite new users to the team.     |
| invitation.destroy | Revoke invitations.               |

Additionally, the `InvitationPolicy` contains a method for responding (`responsd`, meaning to accept or decline) an Invitation, which only the invitee will pass.

| Capability         | Description                              |
| ------------------ | ---------------------------------------- |
| membership.viewAny | View all the memberships to the team.    |
| membership.view    | View the memberships to the team.        |
| membership.update  | Update the roles of members in the team. |
| membership.destroy | Revoke memberships.                      |

If you want to customise these policies then you can change the policies used in the Teamsy config.You can publish the existing policies them with the following command:

```
php artisan vendor:publish --tag=teamsy-policy
```

## Sleepy Integration

Teamsy will automatically detect if you're using [Sleepy](https://github.com/kyletcooper/sleepy) and will register some helpful macros to allow you to add Teamsy API routes.

These routes use the `MembershipPolicy` and `InvitationPolicy` to guard them. You can learn more about these in the [policies(#policies) section.

```php
API::model( Team::class, function(){
	API::teamsy( Team::class );

	/**
	 * Adds the following routes:
	 *
	 * /team/{team}/membership/
	 * /team/{team}/membership/{membership}/
	 *
	 * /team/{team}/invitation/
	 * /team/{team}/invitation/{invitation}/
	 * /team/{team}/invitation/{invitation}/respond/
	 *
	 * /team/{team}/role/
	 *
	 */
});
```

> :bulb: Under the hood, `API::teamsy()` is just a shorthand for running `API::memberships()`, `API::invitations()` and `API::roles()` all at once.

## To-do

- [ ] Add console command to clean up any teams with no users.

- [ ] Add protection, preventing a user from changing their role to owner.

- [ ] Add protection preventing the owner from being removed or demoted.
