<?php

declare(strict_types=1);

namespace Tops;

final class PlaytimeTracker {
	/** @var array<string, array{name: string, start: int}> lowercase name => session data */
	private array $sessions = [];

	public function start(string $playerName): void {
		$this->sessions[strtolower($playerName)] = ["name" => $playerName, "start" => time()];
	}

	public function stop(string $playerNameLower): int {
		$session = $this->sessions[$playerNameLower] ?? null;
		unset($this->sessions[$playerNameLower]);
		if ($session === null) {
			return 0;
		}

		return max(0, time() - $session["start"]);
	}

	// Rebases session start to now so a later flush never re-counts already-persisted seconds.
	/**
	 * @return array<string, array{name: string, seconds: int}> lowercase name => elapsed seconds since last flush
	 */
	public function flushAndRebase(): array {
		$now = time();
		$elapsed = [];
		foreach ($this->sessions as $nameLower => $session) {
			$delta = $now - $session["start"];
			if ($delta > 0) {
				$elapsed[$nameLower] = ["name" => $session["name"], "seconds" => $delta];
			}
			$this->sessions[$nameLower]["start"] = $now;
		}

		return $elapsed;
	}

	public function liveSeconds(string $playerNameLower): int {
		$session = $this->sessions[$playerNameLower] ?? null;

		return $session === null ? 0 : max(0, time() - $session["start"]);
	}

	/**
	 * @return array<string, string> lowercase name => real name, for players currently being tracked
	 */
	public function trackedNames(): array {
		return array_map(static fn(array $session): string => $session["name"], $this->sessions);
	}
}
