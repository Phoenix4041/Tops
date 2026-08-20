<?php

declare(strict_types=1);

namespace Tops;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use Tops\async\FetchTopListsTask;
use Tops\async\IncrementCounterTask;
use Tops\async\InitSchemaTask;
use Tops\async\SetMoneyTask;

final class StatsRepository {
	private const WORKER_ID = 0;

	public function __construct(
		private readonly string $dbPath
	) {
	}

	public function initSchema(): void {
		$this->submit(new InitSchemaTask($this->dbPath));
	}

	public function recordKill(string $playerNameLower): void {
		$this->submit(new IncrementCounterTask($this->dbPath, $playerNameLower, "kills", 1));
	}

	public function recordDeath(string $playerNameLower): void {
		$this->submit(new IncrementCounterTask($this->dbPath, $playerNameLower, "deaths", 1));
	}

	public function addPlaytime(string $playerNameLower, int $secondsToAdd): void {
		if ($secondsToAdd <= 0) {
			return;
		}
		$this->submit(new IncrementCounterTask($this->dbPath, $playerNameLower, "playtime_seconds", $secondsToAdd));
	}

	public function setMoney(string $playerNameLower, float $amount): void {
		$this->submit(new SetMoneyTask($this->dbPath, $playerNameLower, $amount));
	}

	public function fetchTopLists(int $limit): void {
		$this->submit(new FetchTopListsTask($this->dbPath, $limit));
	}

	private function submit(AsyncTask $task): void {
		try {
			Server::getInstance()->getAsyncPool()->submitTaskToWorker($task, self::WORKER_ID);
		} catch (\RuntimeException $e) {
			// The pinned worker can be killed by an unrelated plugin's task; don't compound that into a second crash.
			Loader::getInstance()->getLogger()->warning("Could not submit " . $task::class . ": " . $e->getMessage());
		}
	}
}
