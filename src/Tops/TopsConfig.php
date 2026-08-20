<?php

declare(strict_types=1);

namespace Tops;

final class TopsConfig {
	private const VALID_PROVIDERS = ["none", "bedrockeconomy", "economyapi"];

	private function __construct(
		public readonly string $databaseFileName,
		public readonly string $economyProvider,
		public readonly int $topSize,
		public readonly int $refreshIntervalTicks,
		public readonly int $moneySyncIntervalTicks,
		public readonly int $moneySyncBatchSize,
		public readonly int $playtimeFlushIntervalTicks,
		public readonly string $messagePrefix
	) {
	}

	/**
	 * @param array<int|string, mixed> $raw
	 */
	public static function fromArray(array $raw): self {
		$persistence = self::section($raw, "persistence");
		$economy = self::section($raw, "economy");
		$hologram = self::section($raw, "hologram");
		$messages = self::section($raw, "messages");

		$provider = strtolower(self::toStringOrDefault($economy["provider"] ?? null, "none"));
		if (!in_array($provider, self::VALID_PROVIDERS, true)) {
			$provider = "none";
		}

		return new self(
			databaseFileName: self::toStringOrDefault($persistence["database-file"] ?? null, "tops.sqlite"),
			economyProvider: $provider,
			topSize: self::clampInt($hologram["top-size"] ?? null, 10, 1, 25),
			refreshIntervalTicks: self::clampInt($hologram["refresh-interval-ticks"] ?? null, 100, 20, 12000),
			moneySyncIntervalTicks: self::clampInt($hologram["money-sync-interval-ticks"] ?? null, 200, 20, 12000),
			moneySyncBatchSize: self::clampInt($hologram["money-sync-batch-size"] ?? null, 20, 1, 500),
			playtimeFlushIntervalTicks: self::clampInt($hologram["playtime-flush-interval-ticks"] ?? null, 6000, 1200, 72000),
			messagePrefix: self::toStringOrDefault($messages["prefix"] ?? null, "§8[§bTops§8] §r"),
		);
	}

	/**
	 * @param array<int|string, mixed> $raw
	 * @return array<int|string, mixed>
	 */
	private static function section(array $raw, string $key): array {
		$value = $raw[$key] ?? [];

		return is_array($value) ? $value : [];
	}

	private static function toStringOrDefault(mixed $value, string $default): string {
		return is_scalar($value) ? (string) $value : $default;
	}

	private static function clampInt(mixed $value, int $default, int $min, int $max): int {
		$int = is_numeric($value) ? (int) $value : $default;

		return max($min, min($max, $int));
	}
}
