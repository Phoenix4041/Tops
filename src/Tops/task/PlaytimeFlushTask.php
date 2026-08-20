<?php

declare(strict_types=1);

namespace Tops\task;

use pocketmine\scheduler\Task;
use Tops\PlaytimeTracker;
use Tops\StatsRepository;

final class PlaytimeFlushTask extends Task {

	public function __construct(
		private readonly PlaytimeTracker $tracker,
		private readonly StatsRepository $repository
	) {
	}

	public function onRun(): void {
		foreach ($this->tracker->flushAndRebase() as $nameLower => $elapsedSeconds) {
			$this->repository->addPlaytime($nameLower, $elapsedSeconds);
		}
	}
}
