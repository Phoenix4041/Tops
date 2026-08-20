<?php

declare(strict_types=1);

namespace Tops;

final class CategoryDisplay {

	public function __construct(
		public readonly string $title,
		private readonly string $top1,
		private readonly string $top2,
		private readonly string $top3,
		private readonly string $lineFormat
	) {
	}

	public function formatLine(int $pos, string $name, string $value): string {
		$template = match ($pos) {
			1 => $this->top1,
			2 => $this->top2,
			3 => $this->top3,
			default => $this->lineFormat,
		};

		return str_replace(["{pos}", "{name}", "{value}"], [(string) $pos, $name, $value], $template);
	}
}
