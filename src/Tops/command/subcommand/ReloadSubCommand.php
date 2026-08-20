<?php

declare(strict_types=1);

namespace Tops\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use Tops\Loader;
use Tops\Permissions;

final class ReloadSubCommand extends BaseSubCommand {

	public function __construct(
		private readonly Loader $loader
	) {
		parent::__construct("reload", "Recarga config.yml");
	}

	protected function prepare(): void {
		$this->setPermission(Permissions::RELOAD);
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		$this->loader->reloadPluginConfig();
		$sender->sendMessage(
			"§aconfig.yml recargado. §7El proveedor de economia y los intervalos de las tareas requieren reiniciar el servidor para aplicarse."
		);
	}
}
