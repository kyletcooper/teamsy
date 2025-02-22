<?php

namespace WRD\Teamsy\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use WRD\Teamsy\Capabilities\Role;

/**
 * Contract for items that are like a membership - they connect a user and a team with a role.
 * 
 * Memberships and Invitations implement this contract.
 */
interface Membershipish{
	public function getTeam(): ?Model;

	public function getMember(): ?Model;

	public function getRole(): Role;

	public function getCreatedAt(): Carbon;

	public function revoke(): void;
}