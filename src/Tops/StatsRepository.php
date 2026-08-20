<?php

declare(strict_types=1);

namespace Tops;

use pocketmine\Server;
use Tops\async\FetchTopListsTask;
use Tops\async\IncrementCounterTask;
use Tops\async\InitSchemaTask;
use Tops\async\SetMoneyTask;

final class StatsRepository {

	public function __construct(
		private readonly string $dbPath
	) {
	}

	public function initSchema(): void {
		Server::getInstance()->getAsyncPool()->submitTask(new InitSchemaTask($this->dbPath));
	}

	public function recordKill(string $playerNameLower): void {
		Server::getInstance()->getAsyncPool()->submitTask(
			new IncrementCounterTask($this->dbPath, $playerNameLower, "kills", 1)
		);
	}

	public function recordDeath(string $playerNameLower): void {
		Server::getInstance()->getAsyncPool()->submitTask(
			new IncrementCounterTask($this->dbPath, $playerNameLower, "deaths", 1)
		);
	}

	public function addPlaytime(string $playerNameLower, int $secondsToAdd): void {
		if ($secondsToAdd <= 0) {
			return;
		}
		Server::getInstance()->getAsyncPool()->submitTask(
			new IncrementCounterTask($this->dbPath, $playerNameLower, "playtime_seconds", $secondsToAdd)
		);
	}

	public function setMoney(string $playerNameLower, float $amount): void {
		Server::getInstance()->getAsyncPool()->submitTask(
			new SetMoneyTask($this->dbPath, $playerNameLower, $amount)
		);
	}

	public function fetchTopLists(int $limit): void {
		Server::getInstance()->getAsyncPool()->submitTask(new FetchTopListsTask($this->dbPath, $limit));
	}
}
