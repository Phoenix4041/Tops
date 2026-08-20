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
					kills INTEGER NOT NULL DEFAULT 0,
					deaths INTEGER NOT NULL DEFAULT 0,
					money REAL NOT NULL DEFAULT 0,
					playtime_seconds INTEGER NOT NULL DEFAULT 0
				)"
			);
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
