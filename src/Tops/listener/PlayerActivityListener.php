<?php

declare(strict_types=1);

namespace Tops\listener;

use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use Tops\PlaytimeTracker;
use Tops\StatsRepository;
use Tops\task\MoneySyncTask;

final class PlayerActivityListener implements Listener {

	public function __construct(
		private readonly StatsRepository $repository,
		private readonly PlaytimeTracker $playtimeTracker,
		private readonly ?EconomyProvider $economyProvider,
		private readonly ?MoneySyncTask $moneySyncTask
	) {
	}

	public function onJoin(PlayerJoinEvent $event): void {
		$player = $event->getPlayer();
		$this->playtimeTracker->start($player->getName());
		$this->syncMoneyNow($player);
	}

	public function onQuit(PlayerQuitEvent $event): void {
		$player = $event->getPlayer();
		$name = $player->getName();
		$nameLower = strtolower($name);

		$elapsed = $this->playtimeTracker->stop($nameLower);
		$this->repository->addPlaytime($name, $elapsed);

		$this->syncMoneyNow($player);
		$this->moneySyncTask?->forget($nameLower);
	}

	public function onDeath(PlayerDeathEvent $event): void {
		$victim = $event->getPlayer();
		$this->repository->recordDeath($victim->getName());

		$cause = $victim->getLastDamageCause();
		if (!$cause instanceof EntityDamageByEntityEvent) {
			return;
		}
		$killer = $cause->getDamager();
		if (!$killer instanceof Player || $killer === $victim) {
			return;
		}
		$this->repository->recordKill($killer->getName());
	}

	private function syncMoneyNow(Player $player): void {
		if ($this->economyProvider === null) {
			return;
		}
		$this->economyProvider->getMoney($player, function (float|int $amount) use ($player): void {
			if (!$player->isConnected()) {
				return;
			}
			$this->repository->setMoney($player->getName(), (float) $amount);
		});
	}
}
