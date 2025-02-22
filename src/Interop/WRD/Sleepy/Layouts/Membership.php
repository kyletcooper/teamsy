<?php

namespace WRD\Teamsy\Interop\WRD\Sleepy\Layouts;

use WRD\Sleepy\Layouts\Layout;
use WRD\Sleepy\Schema\Schema;
use WRD\Sleepy\Layouts\Datetime;

class Membership extends Layout{
	public function schema(): Schema{
		$userSchema = config('teamsy.models.user')::getSchema();
		$datetime = new Datetime();
		$role = new Role();

		return Schema::object([
			'id' => Schema::number(),
			'member' => $userSchema->nullable(),
			'created_at' => $datetime->schema()->nullable(),
			'role' => $role->schema(),
		]);
	}

	/**
	 * Present the value.
	 * 
	 * @param \WRD\Teamsy\Models\Membership $value
	 */
	public function present( $value ): array{
		$layoutDatetime = new Datetime();
		$layoutRole = new Role();

		return [
			'id' => $value->getKey(),
			'member' => $value->getMember()?->toApi(),
			'created_at' => $layoutDatetime->present( $value->getCreatedAt() ),
			'role' => $layoutRole->present( $value->getRole() ),
		];
	}
}