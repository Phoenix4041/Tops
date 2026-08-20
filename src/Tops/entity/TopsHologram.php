<?php

declare(strict_types=1);

namespace Tops\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use Tops\TopCategory;

// FloatingTextParticle was removed in PM5; a nameTag on an inert entity is the replacement.
final class TopsHologram extends Entity {
	private const TAG_CATEGORY = "topsCategory";

	private TopCategory $category = TopCategory::KILLS;

	public static function getNetworkTypeId(): string {
		return EntityIds::ARMOR_STAND;
	}

	protected function initEntity(CompoundTag $nbt): void {
		parent::initEntity($nbt);

		$this->category = TopCategory::tryFrom(
			$nbt->getString(self::TAG_CATEGORY, TopCategory::KILLS->value)
		) ?? TopCategory::KILLS;

		$this->setInvisible(true);
		$this->setHasGravity(false);
		$this->setNameTagVisible(true);
		$this->setNameTagAlwaysVisible(true);
		$this->setScale(0.01);
		$this->setCanSaveWithChunk(true);
		$this->setNameTag("§7Cargando...");
	}

	public function saveNBT(): CompoundTag {
		$nbt = parent::saveNBT();
		$nbt->setString(self::TAG_CATEGORY, $this->category->value);

		return $nbt;
	}

	public function getCategory(): TopCategory {
		return $this->category;
	}

	public function setCategory(TopCategory $category): void {
		$this->category = $category;
	}

	public function updateText(string $text): void {
		if ($this->getNameTag() === $text) {
			return;
		}
		$this->setNameTag($text);
	}

	protected function getInitialSizeInfo(): EntitySizeInfo {
		return new EntitySizeInfo(0.1, 0.1);
	}

	protected function getInitialDragMultiplier(): float {
		return 0.0;
	}

	protected function getInitialGravity(): float {
		return 0.0;
	}
}
