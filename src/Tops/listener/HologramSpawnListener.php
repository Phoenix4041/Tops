<?php

declare(strict_types=1);

namespace Tops\listener;

use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\Listener;
use Tops\entity\TopsHologram;
use Tops\hologram\HologramRegistry;

final class HologramSpawnListener implements Listener {

	public function __construct(
		private readonly HologramRegistry $registry
	) {
	}

	public function onEntitySpawn(EntitySpawnEvent $event): void {
		$entity = $event->getEntity();
		if (!$entity instanceof TopsHologram) {
			return;
		}
		$this->registry->register($entity);
	}
}
