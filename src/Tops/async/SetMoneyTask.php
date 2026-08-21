<?php

declare(strict_types=1);

namespace Tops\async;

use pocketmine\scheduler\AsyncTask;
use SQLite3;
use Tops\Loader;

final class SetMoneyTask extends AsyncTask {

	public function __construct(
		private readonly string $dbPath,
		private readonly string $playerNameLower,
		private readonly string $displayName,
		private readonly float $amount
	) {
	}

	public function onRun(): void {
		try {
			$db = new SQLite3($this->dbPath);
			$db->busyTimeout(5000);
			$stmt = $db->prepare(
				"INSERT INTO player_stats (name, display_name, money) VALUES (:name, :displayName, :amount)
				ON CONFLICT(name) DO UPDATE SET money = :amount, display_name = :displayName"
			);
			if ($stmt === false) {
				throw new \RuntimeException($db->lastErrorMsg());
			}
			$stmt->bindValue(":name", $this->playerNameLower, SQLITE3_TEXT);
			$stmt->bindValue(":displayName", $this->displayName, SQLITE3_TEXT);
			$stmt->bindValue(":amount", $this->amount, SQLITE3_FLOAT);
			$stmt->execute();
			$stmt->close();
			$db->close();
		} catch (\Throwable $e) {
			$this->setResult($e->getMessage());
		}
	}

	public function onCompletion(): void {
		$error = $this->getResult();
		if (is_string($error)) {
			Loader::getInstance()->getLogger()->warning("Failed to sync money for {$this->playerNameLower}: $error");
		}
	}
}
