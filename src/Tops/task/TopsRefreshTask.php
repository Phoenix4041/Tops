<?php

declare(strict_types=1);

namespace Tops\task;

use pocketmine\scheduler\Task;
use Tops\StatsRepository;

final class TopsRefreshTask extends Task {

	public function __construct(
		private readonly StatsRepository $repository,
		private readonly int $topSize
	) {
	}

	public function onRun(): void {
		$this->repository->fetchTopLists($this->topSize);
	}
}
