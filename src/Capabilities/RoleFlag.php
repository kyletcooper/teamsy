<?php

namespace WRD\Teamsy\Capabilities;

enum RoleFlag: string{
	case Owner = "owner";
	case Guest = "guest";
}