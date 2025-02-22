<?php

use WRD\Teamsy\Notifications\InviteCreatedNotification;
use WRD\Teamsy\Policies\InvitationPolicy;
use WRD\Teamsy\Policies\MembershipPolicy;

return [
	'models' => [
		/**
		 * The class name of your User model.
		 */
		'user' => \App\Models\User::class,
	],
	'notifications' => [
		/**
		 * The notification that is sent to the recipient when an invitation is created.
		 */
		'inviteCreated' => InviteCreatedNotification::class
	],
	'policies' => [
		/**
		 * The policy used by the Membership model
		 */
		'membership' => MembershipPolicy::class,

		/**
		 * The policy used by the Invitation model
		 */
		'invitation' => InvitationPolicy::class,
	]
];