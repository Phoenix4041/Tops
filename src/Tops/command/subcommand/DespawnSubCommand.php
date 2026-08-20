<?php

declare(strict_types=1);

namespace Tops\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Tops\command\argument\CategoryArgument;
use Tops\hologram\HologramRegistry;
use Tops\Loader;
use Tops\Permissions;
use Tops\TopCategory;

final class DespawnSubCommand extends BaseSubCommand {
	private const MAX_DISTANCE = 10.0;

	public function __construct(
		private readonly HologramRegistry $registry,
		private readonly Loader $loader
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
		$config = $this->loader->getTopsConfig();

		$nearest = $this->registry->findNearest($category, $sender->getWorld(), $sender->getPosition(), self::MAX_DISTANCE);
		if ($nearest === null) {
			$sender->sendMessage($config->message("not-found", [
				"categoria" => $config->categoryTitle($category),
				"distancia" => (string) (int) self::MAX_DISTANCE,
			]));

			return;
		}

		$this->registry->remove($nearest);
		$nearest->flagForDespawn();
		$sender->sendMessage($config->message("despawned", ["categoria" => $config->categoryTitle($category)]));
	}
}
