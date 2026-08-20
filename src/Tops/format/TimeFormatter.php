<?php

declare(strict_types=1);

namespace Tops\format;

final class TimeFormatter {
	private const SECONDS_PER_DAY = 86400;
	private const SECONDS_PER_HOUR = 3600;
	private const SECONDS_PER_MINUTE = 60;

	private function __construct() {
	}

	public static function format(
		int $totalSeconds,
		string $daySuffix = "d",
		string $hourSuffix = "h",
		string $minuteSuffix = "m",
		string $secondSuffix = "s"
	): string {
		if ($totalSeconds < self::SECONDS_PER_MINUTE) {
			return $totalSeconds . $secondSuffix;
		}

		$days = intdiv($totalSeconds, self::SECONDS_PER_DAY);
		$hours = intdiv($totalSeconds % self::SECONDS_PER_DAY, self::SECONDS_PER_HOUR);
		$minutes = intdiv($totalSeconds % self::SECONDS_PER_HOUR, self::SECONDS_PER_MINUTE);

		$parts = [];
		if ($days > 0) {
			$parts[] = $days . $daySuffix;
		}
		if ($hours > 0) {
			$parts[] = $hours . $hourSuffix;
		}
		if ($minutes > 0 || $parts === []) {
			$parts[] = $minutes . $minuteSuffix;
		}

		return implode(" ", $parts);
	}
}
