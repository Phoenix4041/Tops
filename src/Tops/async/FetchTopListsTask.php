<?php

declare(strict_types=1);

namespace Tops\async;

use pocketmine\scheduler\AsyncTask;
use SQLite3;
use Tops\Loader;

/** @phpstan-type TopRow array{name: string, value: int|float} */
final class FetchTopListsTask extends AsyncTask {
	private const COLUMNS = ["kills", "deaths", "money", "playtime_seconds"];

	public function __construct(
		private readonly string $dbPath,
		private readonly int $limit
	) {
	}

	public function onRun(): void {
		/** @phpstan-var array<string, list<TopRow>> $lists */
		$lists = [];
		try {
			$db = new SQLite3($this->dbPath);
			$db->busyTimeout(5000);
			foreach (self::COLUMNS as $column) {
				$stmt = $db->prepare(
					"SELECT name, {$column} AS value FROM player_stats
					WHERE {$column} > 0 ORDER BY {$column} DESC LIMIT :limit"
				);
				if ($stmt === false) {
					throw new \RuntimeException($db->lastErrorMsg());
				}
				$stmt->bindValue(":limit", $this->limit, SQLITE3_INTEGER);
				$result = $stmt->execute();
				if ($result === false) {
					throw new \RuntimeException($db->lastErrorMsg());
				}

				$rows = [];
				while (is_array($row = $result->fetchArray(SQLITE3_ASSOC))) {
					$rows[] = ["name" => (string) $row["name"], "value" => $row["value"]];
				}
				$stmt->close();
				$lists[$column] = $rows;
			}
			$db->close();
			$this->setResult($lists);
		} catch (\Throwable $e) {
			$this->setResult(["__error" => $e->getMessage()]);
		}
	}

	public function onCompletion(): void {
		$result = $this->getResult();
		if (!is_array($result)) {
			return;
		}
		if (isset($result["__error"])) {
			Loader::getInstance()->getLogger()->warning("Failed to fetch top lists: {$result["__error"]}");

			return;
		}

		/** @phpstan-var array<string, list<TopRow>> $result */
		Loader::getInstance()->onTopListsFetched($result);
	}
}
