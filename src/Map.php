<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

use JetBrains\PhpStorm\Pure;

final class Map
{
	public const ALTITUDE = 0;

	public const TYPE = 1;

	public const MOISTURE = 2;

	public const PRECIPITATION = 3;

	public const POTENTIAL = 4;

	public const BOOL = 5;

	public const DIRECTION = 6;

	public const FLOW = 7;

	public const WATER = 8;

	public const VEGETATION = 9;

	public const FERTILITY = 10;

	public const ARABLE = 11;

	public const LAND = 12;

	public const GOOD = 13;

	private int $width;

	private int $height;

	private int $x = 0;

	private int $y = 0;

	#[Pure] public function __construct(private MapConfig $config, private array $map, private int $type) {
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

	public function Temperature(): float {
		return $this->config->temperature()->forAltitude($this->y, $this->Altitude());
	}

	public function Precipitation(): float {
		return $this->map[$this->y][$this->x][self::PRECIPITATION];
	}

	public function Type(): int {
		return $this->map[$this->y][$this->x][$this->type];
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
