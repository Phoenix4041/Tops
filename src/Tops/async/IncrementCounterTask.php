<?php

declare(strict_types=1);

namespace Tops\async;

use pocketmine\scheduler\AsyncTask;
use SQLite3;
use Tops\Loader;

// $column can't be bound as a parameter, so it's restricted to an allow-list to avoid SQL injection.
final class IncrementCounterTask extends AsyncTask {
	private const ALLOWED_COLUMNS = ["kills", "deaths", "playtime_seconds"];

	public function __construct(
		private readonly string $dbPath,
		private readonly string $playerNameLower,
		private readonly string $column,
		private readonly int $amount
	) {
	}

	public function onRun(): void {
		if (!in_array($this->column, self::ALLOWED_COLUMNS, true)) {
			$this->setResult("Rejected increment on disallowed column '{$this->column}'");
			return;
		}

		try {
			$db = new SQLite3($this->dbPath);
			$db->busyTimeout(5000);
			$stmt = $db->prepare(
				"INSERT INTO player_stats (name, {$this->column}) VALUES (:name, :amount)
				ON CONFLICT(name) DO UPDATE SET {$this->column} = {$this->column} + :amount"
			);
			if ($stmt === false) {
				throw new \RuntimeException($db->lastErrorMsg());
			}
			$stmt->bindValue(":name", $this->playerNameLower, SQLITE3_TEXT);
			$stmt->bindValue(":amount", $this->amount, SQLITE3_INTEGER);
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
			Loader::getInstance()->getLogger()->warning("Failed to increment '{$this->column}' for {$this->playerNameLower}: $error");
		}
	}
}
