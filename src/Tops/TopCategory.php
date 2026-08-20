<?php

declare(strict_types=1);

namespace Tops;

enum TopCategory: string {
	case KILLS = "kills";
	case DEATHS = "deaths";
	case MONEY = "dinero";
	case PLAYTIME = "tiempo";

	public function column(): string {
		return match ($this) {
			self::KILLS => "kills",
			self::DEATHS => "deaths",
			self::MONEY => "money",
			self::PLAYTIME => "playtime_seconds",
		};
	}
}
