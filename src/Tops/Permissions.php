<?php

declare(strict_types=1);

namespace Tops;

final class Permissions {
	public const BASE = "tops.command";
	public const SPAWN = "tops.command.spawn";
	public const DESPAWN = "tops.command.despawn";
	public const RELOAD = "tops.command.reload";

	private function __construct() {
	}
}
