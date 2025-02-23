<?php

namespace WRD\Teamsy\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use WRD\Teamsy\Models\Invitation;

class InviteCreated
{
    use Dispatchable, SerializesModels;

    public Invitation $invitation;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }
}