<?php

namespace WRD\Teamsy\Listeners;

use Illuminate\Support\Facades\Auth;
use WRD\Teamsy\Events\TeamCreated;

class AddOwner
{
    public function handle( TeamCreated $event ) {
		$team = $event->team;

		if( Auth::check() ){
			// Add the user as the owner.
			$team->addMember( Auth::current(), 'owner' );
		}
	}
}