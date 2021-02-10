<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

use JetBrains\PhpStorm\Pure;

final class Map
{
	public const ALTITUDE = 0;

	public const TYPE = 1;

	public const TERRAIN_OCEAN = 0;

	public const TERRAIN_PLAIN = 1;

	public const TERRAIN_HIGHLAND = 2;

	public const TERRAIN_MOUNTAIN = 3;

	private int $width;

	private int $height;

	private int $x = 0;

	private int $y = 0;

	#[Pure] public function __construct(private array $map) {
		$this->width  = count($map[0]);
		$this->height = count($map);
	}

	public function Width(): int {
		return $this->width;
	}

	public function Height(): int {
		return $this->height;
	}

	public function Altitude(): int {
		return $this->map[$this->y][$this->x][self::ALTITUDE];
	}

	public function Type(): int {
		return $this->map[$this->y][$this->x][self::TYPE];
	}

	public function setX(int $x): self {
		$this->x = $x;
		return $this;
	}

	public function setY(int $y): self {
		$this->y = $y;
		return $this;
	}

	public function to(int $x, int $y): self {
		return $this->setX($x)->setY($y);
	}
}
