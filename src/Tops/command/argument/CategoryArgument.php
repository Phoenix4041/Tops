<?php

declare(strict_types=1);

namespace Tops\command\argument;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use Tops\TopCategory;

final class CategoryArgument extends StringEnumArgument {
	protected const VALUES = [
		"kills" => TopCategory::KILLS,
		"deaths" => TopCategory::DEATHS,
		"dinero" => TopCategory::MONEY,
		"tiempo" => TopCategory::PLAYTIME,
	];

	public function parse(string $argument, CommandSender $sender): TopCategory {
		$category = self::VALUES[strtolower($argument)] ?? null;
		if (!$category instanceof TopCategory) {
			throw new \LogicException("Unreachable: canParse() already validated '$argument'");
		}

		return $category;
	}

	public function getTypeName(): string {
		return "categoria";
	}
}
