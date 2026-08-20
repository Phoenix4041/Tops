<?php

declare(strict_types=1);

namespace Tops\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use Tops\command\argument\CategoryArgument;
use Tops\entity\TopsHologram;
use Tops\Loader;
use Tops\Permissions;
use Tops\TopCategory;

final class SpawnSubCommand extends BaseSubCommand {

	public function __construct(
		private readonly Loader $loader
	) {
		parent::__construct("spawn", "Spawnea un holograma de top en tu posicion");
	}

	protected function prepare(): void {
		$this->registerArgument(0, new CategoryArgument("categoria"));
		$this->addConstraint(new InGameRequiredConstraint($this));
		$this->setPermission(Permissions::SPAWN);
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

		$pos = $sender->getPosition()->add(0, 2, 0);
		$location = Location::fromObject(
			$pos,
			$sender->getWorld(),
			$sender->getLocation()->getYaw(),
			$sender->getLocation()->getPitch()
		);

		$entity = new TopsHologram($location, null);
		$entity->setCategory($category);
		$entity->spawnToAll();

		$config = $this->loader->getTopsConfig();
		$sender->sendMessage($config->message("spawned", ["categoria" => $config->categoryTitle($category)]));
	}
}
