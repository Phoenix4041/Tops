<?php

declare(strict_types=1);

namespace Tops;

final class PlaytimeTracker {
	/** @var array<string, int> lowercase name => session start (unix timestamp) */
	private array $sessionStart = [];

	public function start(string $playerNameLower): void {
		$this->sessionStart[$playerNameLower] = time();
	}

	public function stop(string $playerNameLower): int {
		$start = $this->sessionStart[$playerNameLower] ?? null;
		unset($this->sessionStart[$playerNameLower]);
		if ($start === null) {
			return 0;
		}

		return max(0, time() - $start);
	}

	// Rebases session start to now so a later flush never re-counts already-persisted seconds.
	/**
	 * @return array<string, int> lowercase name => elapsed seconds since last flush
	 */
	public function flushAndRebase(): array {
		$now = time();
		$elapsed = [];
		foreach ($this->sessionStart as $name => $start) {
			$delta = $now - $start;
			if ($delta > 0) {
				$elapsed[$name] = $delta;
			}
			$this->sessionStart[$name] = $now;
		}

		return $elapsed;
	}
}
