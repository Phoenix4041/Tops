<?php

declare(strict_types=1);

namespace Tops;

use pocketmine\utils\TextFormat;

final class TopsConfig {
	private const VALID_PROVIDERS = ["none", "bedrockeconomy", "economyapi"];

	private const DEFAULT_CATEGORIES = [
		"kills" => ["title" => "&l&6Kills", "top1" => "&e{pos}. &l&6{name} &r&f{value}", "top2" => "&e{pos}. &l&6{name} &r&f{value}", "top3" => "&e{pos}. &l&6{name} &r&f{value}", "line-format" => "&7{pos}. &f{name} &e{value}"],
		"deaths" => ["title" => "&l&cMuertes", "top1" => "&e{pos}. &l&c{name} &r&f{value}", "top2" => "&e{pos}. &l&c{name} &r&f{value}", "top3" => "&e{pos}. &l&c{name} &r&f{value}", "line-format" => "&7{pos}. &f{name} &e{value}"],
		"dinero" => ["title" => "&l&aDinero", "top1" => "&e{pos}. &l&a{name} &r&f{value}", "top2" => "&e{pos}. &l&a{name} &r&f{value}", "top3" => "&e{pos}. &l&a{name} &r&f{value}", "line-format" => "&7{pos}. &f{name} &e{value}"],
		"tiempo" => ["title" => "&l&bTiempo Jugado", "top1" => "&e{pos}. &l&b{name} &r&f{value}", "top2" => "&e{pos}. &l&b{name} &r&f{value}", "top3" => "&e{pos}. &l&b{name} &r&f{value}", "line-format" => "&7{pos}. &f{name} &e{value}"],
	];

	private const DEFAULT_MESSAGES = [
		"spawned" => "&aHolograma de {categoria} spawneado.",
		"despawned" => "&aHolograma de {categoria} eliminado.",
		"not-found" => "&cNo hay ningun holograma de {categoria} a menos de {distancia} bloques.",
		"reloaded" => "&aconfig.yml recargado.",
	];

	/**
	 * @param array<string, CategoryDisplay> $categoryDisplays category value => display
	 * @param array<string, string> $messages key => colorized template (prefix not yet applied)
	 */
	private function __construct(
		public readonly string $databaseFileName,
		public readonly string $economyProvider,
		public readonly int $topSize,
		public readonly int $refreshIntervalTicks,
		public readonly int $moneySyncIntervalTicks,
		public readonly int $moneySyncBatchSize,
		public readonly int $playtimeFlushIntervalTicks,
		public readonly int $moneyDecimals,
		public readonly string $playtimeDaySuffix,
		public readonly string $playtimeHourSuffix,
		public readonly string $playtimeMinuteSuffix,
		public readonly string $playtimeSecondSuffix,
		public readonly string $noDataMessage,
		public readonly array $categoryDisplays,
		private readonly string $messagePrefix,
		private readonly array $messages
	) {
	}

	/**
	 * @param array<int|string, mixed> $config config.yml contents
	 * @param array<int|string, mixed> $messages messages.yml contents
	 */
	public static function fromArray(array $config, array $messages): self {
		$persistence = self::section($config, "persistence");
		$economy = self::section($config, "economy");
		$hologram = self::section($config, "hologram");
		$money = self::section($hologram, "money");
		$playtime = self::section($hologram, "playtime");
		$categoriesRaw = self::section($messages, "categories");

		$provider = strtolower(self::toStringOrDefault($economy["provider"] ?? null, "none"));
		if (!in_array($provider, self::VALID_PROVIDERS, true)) {
			$provider = "none";
		}

		$categoryDisplays = [];
		foreach (self::DEFAULT_CATEGORIES as $categoryValue => $defaults) {
			$raw = self::stringMap(self::section($categoriesRaw, $categoryValue), $defaults);
			$categoryDisplays[$categoryValue] = new CategoryDisplay(
				title: TextFormat::colorize($raw["title"]),
				top1: TextFormat::colorize($raw["top1"]),
				top2: TextFormat::colorize($raw["top2"]),
				top3: TextFormat::colorize($raw["top3"]),
				lineFormat: TextFormat::colorize($raw["line-format"]),
			);
		}

		return new self(
			databaseFileName: self::toStringOrDefault($persistence["database-file"] ?? null, "tops.sqlite"),
			economyProvider: $provider,
			topSize: self::clampInt($hologram["top-size"] ?? null, 10, 1, 25),
			refreshIntervalTicks: self::clampInt($hologram["refresh-interval-ticks"] ?? null, 100, 20, 12000),
			moneySyncIntervalTicks: self::clampInt($hologram["money-sync-interval-ticks"] ?? null, 200, 20, 12000),
			moneySyncBatchSize: self::clampInt($hologram["money-sync-batch-size"] ?? null, 20, 1, 500),
			playtimeFlushIntervalTicks: self::clampInt($hologram["playtime-flush-interval-ticks"] ?? null, 6000, 1200, 72000),
			moneyDecimals: self::clampInt($money["decimals"] ?? null, 2, 0, 8),
			playtimeDaySuffix: self::toStringOrDefault($playtime["day-suffix"] ?? null, "d"),
			playtimeHourSuffix: self::toStringOrDefault($playtime["hour-suffix"] ?? null, "h"),
			playtimeMinuteSuffix: self::toStringOrDefault($playtime["minute-suffix"] ?? null, "m"),
			playtimeSecondSuffix: self::toStringOrDefault($playtime["second-suffix"] ?? null, "s"),
			noDataMessage: TextFormat::colorize(self::toStringOrDefault($messages["no-data-message"] ?? null, "&7Sin datos todavia")),
			categoryDisplays: $categoryDisplays,
			messagePrefix: TextFormat::colorize(self::toStringOrDefault($messages["prefix"] ?? null, "&8[&bTops&8] &r")),
			messages: array_map(TextFormat::colorize(...), self::stringMap($messages, self::DEFAULT_MESSAGES)),
		);
	}

	public function display(TopCategory $category): CategoryDisplay {
		return $this->categoryDisplays[$category->value];
	}

	/**
	 * @param array<string, string> $placeholders
	 */
	public function message(string $key, array $placeholders = []): string {
		$template = $this->messages[$key] ?? "";
		$text = $this->messagePrefix . $template;
		foreach ($placeholders as $name => $value) {
			$text = str_replace("{" . $name . "}", $value, $text);
		}

		return $text;
	}

	public function categoryTitle(TopCategory $category): string {
		return $this->display($category)->title;
	}

	/**
	 * @param array<int|string, mixed> $raw
	 * @return array<int|string, mixed>
	 */
	private static function section(array $raw, string $key): array {
		$value = $raw[$key] ?? [];

		return is_array($value) ? $value : [];
	}

	/**
	 * @param array<int|string, mixed> $raw
	 * @param array<string, string> $defaults
	 * @return array<string, string>
	 */
	private static function stringMap(array $raw, array $defaults): array {
		$result = $defaults;
		foreach ($defaults as $key => $default) {
			if (isset($raw[$key]) && is_scalar($raw[$key])) {
				$result[$key] = (string) $raw[$key];
			}
		}

		return $result;
	}

	private static function toStringOrDefault(mixed $value, string $default): string {
		return is_scalar($value) ? (string) $value : $default;
	}

	private static function clampInt(mixed $value, int $default, int $min, int $max): int {
		$int = is_numeric($value) ? (int) $value : $default;

		return max($min, min($max, $int));
	}
}
