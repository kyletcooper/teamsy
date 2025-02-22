<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy\Layouts;

use WRD\Sleepy\Layouts\Layout;
use WRD\Sleepy\Schema\Schema;
use WRD\Sleepy\Layouts\Datetime;
use WRD\Teamsy\Enums\InvitationStatus;

class Invitation extends Layout{
	public function schema(): Schema{
		$userSchema = config('teamsy.models.user')::getSchema();
		$datetime = new Datetime();
		$role = new Role();

		return Schema::object([
			'id' => Schema::number(),
			'status' => Schema::string()->enum( InvitationStatus::cases() ),
			'invitee' => $userSchema->nullable(),
			'sender' => $userSchema->nullable(),
			'created_at' => $datetime->schema()->nullable(),
			'role' => $role->schema(),
			'message' => Schema::string(),
			'email' => Schema::string( 'email' ),
		]);
	}

	/**
	 * Present the value.
	 * 
	 * @param \WRD\Teamsy\Models\Invitation $value
	 */
	public function present( $value ): array{
		$layoutDatetime = new Datetime();
		$layoutRole = new Role();

		return [
			'id' => $value->getKey(),
			'status' => $value->getStatus()->value,
			'invitee' => $value->getInvitee()?->toApi(),
			'sender' => $value->getSender()?->toApi(),
			'created_at' => $layoutDatetime->present( $value->getCreatedAt() ),
			'role' => $layoutRole->present( $value->getRole() ),
			'message' => $value->message,
			'email' => $value->email,
		];
	}
}