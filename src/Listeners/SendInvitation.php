<?php

namespace WRD\Teamsy\Listeners;

use Illuminate\Support\Facades\Notification;
use WRD\Teamsy\Events\InviteCreated;

class SendInvitation
{
    public function handle( InviteCreated $event ) {
        $invitation = $event->invitation;
		$notificationClass = config("teamsy.notifications.inviteCreated");
        $notification = new $notificationClass( $invitation );
        $user = $invitation->getInvitee();
        
        if( ! is_null( $user ) ){
            $user->notify( $notification );
        }
        else{
            Notification::route( 'mail', $invitation->email )
                ->notify( $notification );
        }
    }
}