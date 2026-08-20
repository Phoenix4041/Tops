<?php

declare(strict_types=1);

namespace Tops\hologram;

use pocketmine\math\Vector3;
use pocketmine\world\World;
use Tops\entity\TopsHologram;
use Tops\TopCategory;
use WeakReference;

final class HologramRegistry {
	/** @var array<string, list<WeakReference<TopsHologram>>> */
	private array $byCategory = [];

	public function register(TopsHologram $entity): void {
		$this->byCategory[$entity->getCategory()->value][] = WeakReference::create($entity);
	}

	/**
	 * @return list<TopsHologram>
	 */
	public function getLive(TopCategory $category): array {
		$live = [];
		$kept = [];
		foreach ($this->byCategory[$category->value] ?? [] as $ref) {
			$entity = $ref->get();
			if ($entity === null || $entity->isClosed() || $entity->isFlaggedForDespawn()) {
				continue;
			}
			$kept[] = $ref;
			$live[] = $entity;
		}
		$this->byCategory[$category->value] = $kept;

		return $live;
	}

	public function findNearest(TopCategory $category, World $world, Vector3 $pos, float $maxDistance): ?TopsHologram {
		$nearest = null;
		$nearestDistSq = $maxDistance * $maxDistance;
		foreach ($this->getLive($category) as $entity) {
			if ($entity->getWorld() !== $world) {
				continue;
			}
			$distSq = $entity->getPosition()->distanceSquared($pos);
			if ($distSq <= $nearestDistSq) {
				$nearestDistSq = $distSq;
				$nearest = $entity;
			}
		}

		return $nearest;
	}

	public function remove(TopsHologram $entity): void {
		$category = $entity->getCategory()->value;
		$this->byCategory[$category] = array_values(array_filter(
			$this->byCategory[$category] ?? [],
			static fn(WeakReference $ref): bool => $ref->get() !== $entity
		));
	}
}
