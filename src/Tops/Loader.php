<?php

declare(strict_types=1);

namespace Tops;

use DaPigGuy\libPiggyEconomy\exceptions\MissingProviderDependencyException;
use DaPigGuy\libPiggyEconomy\exceptions\UnknownProviderException;
use DaPigGuy\libPiggyEconomy\libPiggyEconomy;
use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\World;
use Tops\command\TopsCommand;
use Tops\entity\TopsHologram;
use Tops\format\TimeFormatter;
use Tops\hologram\HologramRegistry;
use Tops\listener\HologramSpawnListener;
use Tops\listener\PlayerActivityListener;
use Tops\task\MoneySyncTask;
use Tops\task\PlaytimeFlushTask;
use Tops\task\TopsRefreshTask;

final class Loader extends PluginBase {
	private static ?Loader $instance = null;

	private TopsConfig $config;
	private StatsRepository $repository;
	private HologramRegistry $hologramRegistry;
	private PlaytimeTracker $playtimeTracker;
	private ?EconomyProvider $economyProvider = null;
	private ?MoneySyncTask $moneySyncTask = null;

	/** @var TaskHandler<TopsRefreshTask> */
	private TaskHandler $refreshHandler;
	/** @var TaskHandler<PlaytimeFlushTask> */
	private TaskHandler $playtimeFlushHandler;
	/** @var TaskHandler<MoneySyncTask>|null */
	private ?TaskHandler $moneySyncHandler = null;

	public static function getInstance(): self {
		return self::$instance ?? throw new \RuntimeException("Tops is not enabled");
	}

	protected function onEnable(): void {
		self::$instance = $this;

		$this->saveDefaultConfig();
		$this->config = TopsConfig::fromArray($this->getConfig()->getAll());

		$this->repository = new StatsRepository($this->getDataFolder() . $this->config->databaseFileName);
		$this->repository->initSchema();

		$this->hologramRegistry = new HologramRegistry();
		$this->playtimeTracker = new PlaytimeTracker();
		$this->economyProvider = $this->resolveEconomyProvider();

		EntityFactory::getInstance()->register(
			TopsHologram::class,
			static fn(World $world, CompoundTag $nbt): TopsHologram => new TopsHologram(EntityDataHelper::parseLocation($nbt, $world), $nbt),
			["tops:hologram"]
		);

		$this->getServer()->getCommandMap()->register(
			"tops",
			new TopsCommand($this, $this->hologramRegistry, $this)
		);

		if ($this->economyProvider !== null) {
			$this->moneySyncTask = new MoneySyncTask($this->economyProvider, $this->repository, $this->config->moneySyncBatchSize);
		}

		$this->getServer()->getPluginManager()->registerEvents(
			new PlayerActivityListener($this->repository, $this->playtimeTracker, $this->economyProvider, $this->moneySyncTask),
			$this
		);
		$this->getServer()->getPluginManager()->registerEvents(new HologramSpawnListener($this->hologramRegistry), $this);

		$this->refreshHandler = $this->getScheduler()->scheduleRepeatingTask(
			new TopsRefreshTask($this->repository, $this->config->topSize),
			$this->config->refreshIntervalTicks
		);
		$this->playtimeFlushHandler = $this->getScheduler()->scheduleRepeatingTask(
			new PlaytimeFlushTask($this->playtimeTracker, $this->repository),
			$this->config->playtimeFlushIntervalTicks
		);
		if ($this->moneySyncTask !== null) {
			$this->moneySyncHandler = $this->getScheduler()->scheduleRepeatingTask(
				$this->moneySyncTask,
				$this->config->moneySyncIntervalTicks
			);
		}
	}

	protected function onDisable(): void {
		foreach ($this->playtimeTracker->flushAndRebase() as $nameLower => $seconds) {
			$this->repository->addPlaytime($nameLower, $seconds);
		}

		$this->refreshHandler->cancel();
		$this->playtimeFlushHandler->cancel();
		$this->moneySyncHandler?->cancel();

		self::$instance = null;
	}

	public function reloadPluginConfig(): void {
		$this->reloadConfig();
		$this->config = TopsConfig::fromArray($this->getConfig()->getAll());
	}

	/**
	 * @param array<string, list<array{name: string, value: int|float}>> $lists
	 */
	public function onTopListsFetched(array $lists): void {
		foreach (TopCategory::cases() as $category) {
			$rows = $lists[$category->column()] ?? [];
			$text = $this->formatTopText($category, $rows);
			foreach ($this->hologramRegistry->getLive($category) as $entity) {
				$entity->updateText($text);
			}
		}
	}

	/**
	 * @param list<array{name: string, value: int|float}> $rows
	 */
	private function formatTopText(TopCategory $category, array $rows): string {
		if ($rows === []) {
			return $category->displayName() . "\n§7Sin datos todavia";
		}

		$lines = [$category->displayName()];
		$place = 1;
		foreach ($rows as $row) {
			$lines[] = "§7#{$place} §f{$row['name']} §e" . $this->formatValue($category, $row["value"]);
			++$place;
		}

		return implode("\n", $lines);
	}

	private function formatValue(TopCategory $category, int|float $value): string {
		return match ($category) {
			TopCategory::MONEY => number_format((float) $value, 2),
			TopCategory::PLAYTIME => TimeFormatter::format((int) $value),
			default => (string) (int) $value,
		};
	}

	private function resolveEconomyProvider(): ?EconomyProvider {
		if ($this->config->economyProvider === "none") {
			return null;
		}

		libPiggyEconomy::init();
		try {
			return libPiggyEconomy::getProvider(["provider" => $this->config->economyProvider]);
		} catch (MissingProviderDependencyException|UnknownProviderException $e) {
			$this->getLogger()->warning(
				"Proveedor de economia '{$this->config->economyProvider}' no disponible: {$e->getMessage()}. El top de Dinero mostrara 0 hasta que instales ese plugin."
			);

			return null;
		}
	}
}
