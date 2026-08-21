<?php

declare(strict_types=1);

namespace Tops\task;

use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use Tops\StatsRepository;

// Round-robins a batch per tick since some economy plugins resolve balances synchronously.
final class MoneySyncTask extends Task {
	/** @var array<string, float> lowercase name => last value written to our DB */
	private array $lastSynced = [];

	private int $cursor = 0;

	public function __construct(
		private readonly EconomyProvider $provider,
		private readonly StatsRepository $repository,
		private readonly int $batchSize
	) {
	}

	public function onRun(): void {
		$online = array_values(Server::getInstance()->getOnlinePlayers());
		$count = count($online);
		if ($count === 0) {
			return;
		}

		for ($i = 0; $i < min($this->batchSize, $count); ++$i) {
			$player = $online[$this->cursor % $count];
			++$this->cursor;
			$this->syncOne($player);
		}
	}

	private function syncOne(Player $player): void {
		$this->provider->getMoney($player, function (float|int $amount) use ($player): void {
			if (!$player->isConnected()) {
				return;
			}
			$nameLower = strtolower($player->getName());
			$value = (float) $amount;
			if (($this->lastSynced[$nameLower] ?? null) === $value) {
				return;
			}
			$this->lastSynced[$nameLower] = $value;
			$this->repository->setMoney($player->getName(), $value);
		});
	}

	public function forget(string $playerNameLower): void {
		unset($this->lastSynced[$playerNameLower]);
	}
}
