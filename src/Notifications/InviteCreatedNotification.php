<?php

namespace WRD\Teamsy\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use WRD\Teamsy\Models\Invitation;

class InviteCreatedNotification extends Notification
{
    public Invitation $invitation;

    public function __construct(Invitation $invitation) {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via( $notifiable )
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
	 * 
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail( $notifiable )
    {
        return (new MailMessage)
            ->subject( "You've been invited to join a new team!" )
            ->line( $this->invitation->message );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
	 * 
     * @return array
     */
    public function toArray( $notifiable )
    {
        return [
            'invitation_id' => $this->invitation->getKey(),
        ];
    }
}