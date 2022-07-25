<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

use JetBrains\PhpStorm\Pure;

use Lemuria\Exception\LemuriaException;

final class Map implements \ArrayAccess
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

	public const RESOURCE = 14;

	private int $width;

	private int $height;

	private int $x = 0;

	private int $y = 0;

	private string $resource = '';

	#[Pure] public function __construct(private readonly MapConfig $config, private readonly array $map, private readonly int $type) {
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

	public function Resource(): array {
		return $this->map[$this->y][$this->x][self::RESOURCE][$this->resource] ?? [];
	}

	/**
	 * @var int $offset
	 */
	public function offsetExists(mixed $offset): bool {
		return is_int($offset) && isset($this->map[$offset]);
	}

	/**
	 * @var int $offset
	 */
	public function offsetGet(mixed $offset): ?array {
		return $this->offsetExists($offset) ? $this->map[$offset] : null;
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		throw new LemuriaException('Map data cannot be set.');
	}

	public function offsetUnset(mixed $offset): void {
		throw new LemuriaException('Map data cannot be unset.');
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

	public function setResource(string $resource): self {
		$this->resource = $resource;
		return $this;
	}
}
