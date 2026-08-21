<?php

declare(strict_types=1);

namespace Tops\async;

use pocketmine\scheduler\AsyncTask;
use SQLite3;
use Tops\Loader;

final class InitSchemaTask extends AsyncTask {

	public function __construct(
		private readonly string $dbPath
	) {
	}

	public function onRun(): void {
		try {
			$db = new SQLite3($this->dbPath);
			$db->busyTimeout(5000);
			$db->exec(
				"CREATE TABLE IF NOT EXISTS player_stats (
					name TEXT PRIMARY KEY,
					display_name TEXT NOT NULL DEFAULT '',
					kills INTEGER NOT NULL DEFAULT 0,
					deaths INTEGER NOT NULL DEFAULT 0,
					money REAL NOT NULL DEFAULT 0,
					playtime_seconds INTEGER NOT NULL DEFAULT 0
				)"
			);
			$hasDisplayName = false;
			$columns = $db->query("PRAGMA table_info(player_stats)");
			if ($columns === false) {
				throw new \RuntimeException($db->lastErrorMsg());
			}
			while (is_array($column = $columns->fetchArray(SQLITE3_ASSOC))) {
				if ($column["name"] === "display_name") {
					$hasDisplayName = true;
					break;
				}
			}
			if (!$hasDisplayName) {
				$db->exec("ALTER TABLE player_stats ADD COLUMN display_name TEXT NOT NULL DEFAULT ''");
				$db->exec("UPDATE player_stats SET display_name = name WHERE display_name = ''");
			}
			$db->exec("CREATE INDEX IF NOT EXISTS idx_player_stats_kills ON player_stats(kills DESC)");
			$db->exec("CREATE INDEX IF NOT EXISTS idx_player_stats_deaths ON player_stats(deaths DESC)");
			$db->exec("CREATE INDEX IF NOT EXISTS idx_player_stats_money ON player_stats(money DESC)");
			$db->exec("CREATE INDEX IF NOT EXISTS idx_player_stats_playtime ON player_stats(playtime_seconds DESC)");
			$db->close();
		} catch (\Throwable $e) {
			$this->setResult($e->getMessage());
		}
	}

	public function onCompletion(): void {
		$error = $this->getResult();
		if (is_string($error)) {
			Loader::getInstance()->getLogger()->error("Failed to initialize the stats database: $error");
		}
	}
}
