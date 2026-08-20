<?php

declare(strict_types=1);

namespace Tops\entity;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\nbt\tag\CompoundTag;
use Tops\TopCategory;

// FloatingTextParticle was removed in PM5; a falling_block actor rendered as air is the replacement.
final class TopsHologram extends Entity {
	private const TAG_CATEGORY = "topsCategory";

	private TopCategory $category = TopCategory::KILLS;

	public static function getNetworkTypeId(): string {
		return EntityIds::FALLING_BLOCK;
	}

	protected function initEntity(CompoundTag $nbt): void {
		parent::initEntity($nbt);

		$this->category = TopCategory::tryFrom(
			$nbt->getString(self::TAG_CATEGORY, TopCategory::KILLS->value)
		) ?? TopCategory::KILLS;

		$this->setHasGravity(false);
		$this->setCanClimb(false);
		$this->setNoClientPredictions(true);
		$this->setNameTagVisible(true);
		$this->setNameTagAlwaysVisible(true);
		$this->setCanSaveWithChunk(true);
		$this->setNameTag("§7Cargando...");
	}

	protected function syncNetworkData(EntityMetadataCollection $properties): void {
		parent::syncNetworkData($properties);
		$properties->setInt(
			EntityMetadataProperties::VARIANT,
			TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::AIR()->getStateId())
		);
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
