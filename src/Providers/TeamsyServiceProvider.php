<?php

namespace WRD\Teamsy\Providers;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use WRD\Teamsy\Capabilities\RolesRegistry;
use WRD\Teamsy\Console\Commands\AbandonedCommand;
use WRD\Teamsy\Events\InviteCreated;
use WRD\Teamsy\Events\LeavingTeam;
use WRD\Teamsy\Events\MemberDeleting;
use WRD\Teamsy\Events\RoleChanging;
use WRD\Teamsy\Events\TeamCreated;
use WRD\Teamsy\Events\TeamDeleting;
use WRD\Teamsy\Interop\WRD\Sleepy\WRDSleepyServiceProvider;
use WRD\Teamsy\Listeners\AddOwner;
use WRD\Teamsy\Listeners\CleanUpMember;
use WRD\Teamsy\Listeners\CleanUpTeam;
use WRD\Teamsy\Listeners\RevokeLeavingMemberInvitation;
use WRD\Teamsy\Listeners\RevokeNoLongerAllowedInvitations;
use WRD\Teamsy\Listeners\SendInvitation;
use WRD\Teamsy\Models\Invitation;
use WRD\Teamsy\Models\Membership;

final class TeamsyServiceProvider extends ServiceProvider {
	/**
	 * The interop service providers.
	 * 
	 * @var string[] $interopServiceProviders
	 */
	public array $interopServiceProviders = [
		WRDSleepyServiceProvider::class,
	];

	/**
	 * Register this service provider.
	 * 
	 * @return void
	 */
	public function register(): void {
		$this->mergeConfigFrom(
			__DIR__ . '/../../config/teamsy.php', 'teamsy'
		);

		$this->app->bind( 'teamsyRoles', function(){
			return new RolesRegistry();
	    });

		$this->registerInteropServiceProviders();
	}

	/**
	 * Register the interop service providers, if supported.
	 * 
	 * @return void
	 */
	public function registerInteropServiceProviders(): void {
		foreach( $this->interopServiceProviders as $provider ){
			if( $provider::shouldRegister() ){
				$this->app->register( $provider );
			}
		}
	}

	/**
	 * Boot this service provider.
	 * 
	 * @return void
	 */
	public function boot(): void {
		AboutCommand::add('WRD/Teamsy', fn () => ['Version' => '0.0.1']);

		Gate::policy(Membership::class, config("teamsy.policy.membership"));
		Gate::policy(Invitation::class, config("teamsy.policy.invitation"));

		Event::listen(TeamCreated::class, AddOwner::class);
		Event::listen(TeamDeleting::class, CleanUpTeam::class);
		Event::listen(LeavingTeam::class, RevokeLeavingMemberInvitation::class);
		Event::listen(RoleChanging::class, RevokeNoLongerAllowedInvitations::class);

		Event::listen(MemberDeleting::class, CleanUpMember::class);
		
		Event::listen(InviteCreated::class, SendInvitation::class);

		if ($this->app->runningInConsole()) {
			$this->commands([
				AbandonedCommand::class
			]);
		}

		$this->publishes([
			__DIR__ . '/../../config/teamsy.php' => config_path( 'teamsy.php' ),
		], ['teamsy-config', 'teamsy-install']);

		$this->publishesMigrations([
			__DIR__ . '/../../database/migrations' => database_path('migrations'),
		], ['teams-migrations', 'teamsy-install']);
			
		$this->publishes([
			__DIR__ . '/../Notifications/InviteCreatedNotification.php' => './app/Notifications/InviteCreatedNotification.php',
		], 'teamsy-notifications');

		$this->publishes([
			__DIR__ . '/../Policies/InvitationPolicy.php' => './app/Policies/InvitationPolicy.php',
			__DIR__ . '/../Policies/MembershipPolicy.php' => './app/Policies/MembershipPolicy.php',
		], 'teamsy-policy');
	}
}