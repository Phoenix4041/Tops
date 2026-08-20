<?php

declare(strict_types=1);

namespace Tops\command;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use Tops\command\subcommand\DespawnSubCommand;
use Tops\command\subcommand\ReloadSubCommand;
use Tops\command\subcommand\SpawnSubCommand;
use Tops\hologram\HologramRegistry;
use Tops\Loader;

final class TopsCommand extends BaseCommand {

	public function __construct(
		Plugin $plugin,
		private readonly HologramRegistry $registry,
		private readonly Loader $loaderRef
	) {
		parent::__construct($plugin, "tops", "Gestiona los hologramas de tops del servidor");
	}

	protected function prepare(): void {
		$this->registerSubCommand(new SpawnSubCommand($this->loaderRef));
		$this->registerSubCommand(new DespawnSubCommand($this->registry, $this->loaderRef));
		$this->registerSubCommand(new ReloadSubCommand($this->loaderRef));
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		$this->sendUsage();
	}
}
