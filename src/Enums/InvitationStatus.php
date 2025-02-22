<?php

namespace WRD\Teamsy\Enums;

enum InvitationStatus: string
{
    case Pending = 'pending';
	case Declined = 'declined';
}