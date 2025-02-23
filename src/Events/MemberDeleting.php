<?php

namespace WRD\Teamsy\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class MemberDeleting
{
    use Dispatchable, SerializesModels;

    public Model $member;

    public function __construct(Model $member)
    {
        $this->member = $member;
    }
}