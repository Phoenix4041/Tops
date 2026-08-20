<?php

declare(strict_types=1);

namespace Tops\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Tops\command\argument\CategoryArgument;
use Tops\hologram\HologramRegistry;
use Tops\Permissions;
use Tops\TopCategory;

final class DespawnSubCommand extends BaseSubCommand {
	private const MAX_DISTANCE = 10.0;

	public function __construct(
		private readonly HologramRegistry $registry
	) {
		parent::__construct("despawn", "Elimina el holograma de top mas cercano a ti");
	}

	protected function prepare(): void {
		$this->registerArgument(0, new CategoryArgument("categoria"));
		$this->addConstraint(new InGameRequiredConstraint($this));
		$this->setPermission(Permissions::DESPAWN);
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if (!$sender instanceof Player) {
			return;
		}
		/** @var TopCategory $category */
		$category = $args["categoria"];

		$nearest = $this->registry->findNearest($category, $sender->getWorld(), $sender->getPosition(), self::MAX_DISTANCE);
		if ($nearest === null) {
			$sender->sendMessage("§cNo hay ningun holograma de " . $category->displayName() . "§c a menos de " . (int) self::MAX_DISTANCE . " bloques.");

			return;
		}

		$this->registry->remove($nearest);
		$nearest->flagForDespawn();
		$sender->sendMessage("§aHolograma de " . $category->displayName() . "§a eliminado.");
	}
}
