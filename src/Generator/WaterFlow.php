<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Generator;

use JetBrains\PhpStorm\Pure;

use Lemuria\Tools\Lemuria\Map;
use Lemuria\Tools\Lemuria\MapConfig;

trait WaterFlow
{
	private MapConfig $config;

	#[Pure] public function getPrecipitationMap(): Map {
		return new Map($this->map, Map::VEGETATION);
	}

	private array $map;

	private function calculateWaterFlow(MapConfig $config, array &$map): void {
		$this->config = $config;
		$this->map    =& $map;
		$minX = $this->config->offsetX + $this->config->edge;
		$maxX = $this->config->maxX - $this->config->edge;
		$minY = $this->config->offsetY + $this->config->edge;
		$maxY = $this->config->maxY - $this->config->edge;
		$equator      = $config->height / 2;

		// Determine flow direction.
		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				$minimum = $map[$y][$x][Map::ALTITUDE];
				if ($minimum > 0) {
					$direction = $this->minimumAltitude($minimum, Map::DIRECTION_NONE, Map::DIRECTION_NE, $x, $y, 0, 1);
					$direction = $this->minimumAltitude($minimum, $direction, Map::DIRECTION_E, $x, $y, 1, 0);
					$direction = $this->minimumAltitude($minimum, $direction, Map::DIRECTION_SE, $x, $y, 1, -1);
					$direction = $this->minimumAltitude($minimum, $direction, Map::DIRECTION_SW, $x, $y, 0, -1);
					$direction = $this->minimumAltitude($minimum, $direction, Map::DIRECTION_W, $x, $y, -1, 0);
					$direction = $this->minimumAltitude($minimum, $direction, Map::DIRECTION_NW, $x, $y, -1, 1);

					$map[$y][$x][Map::POTENTIAL] = $map[$y][$x][Map::PRECIPITATION];
					$map[$y][$x][Map::BOOL]      = 1;
					$map[$y][$x][Map::DIRECTION] = $direction;
				}
			}
		}

		// Calculate flow from potentials.
		for ($i = 0; $i < 30; $i++) {
			for ($y = $minY; $y < $maxY; $y++) {
				for ($x = $minX; $x < $maxX; $x++) {
					$altitude = $map[$y][$x][Map::ALTITUDE];
					if ($this->maximumAltitude($x, $y, 0, 1, $altitude)) {
						continue;
					}
					if ($this->maximumAltitude($x, $y, 1, 0, $altitude)) {
						continue;
					}
					if ($this->maximumAltitude($x, $y, 1, -1, $altitude)) {
						continue;
					}
					if ($this->maximumAltitude($x, $y, 0, -1, $altitude)) {
						continue;
					}
					if ($this->maximumAltitude($x, $y, -1, 0, $altitude)) {
						continue;
					}
					if ($this->maximumAltitude($x, $y, -1, 1, $altitude)) {
						continue;
					}

					$potential = $map[$y][$x][Map::POTENTIAL];
					$direction = $map[$y][$x][Map::DIRECTION];
					if (!empty($direction)) {
						$map[$y + $direction[1]][$x + $direction[0]][Map::POTENTIAL] += $potential;
					}
					$map[$y][$x][Map::BOOL] = 0;
					$map[$y][$x][Map::FLOW] = $potential;
				}
			}
		}

		// Create oases or swamps when slope is low and flow is great.
		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				$flow = $map[$y][$x][Map::FLOW];
				if ($flow > 0.0) {
					$direction     = $map[$y][$x][Map::DIRECTION];
					$precipitation = $map[$y][$x][Map::PRECIPITATION];

					if (empty($direction)) {
						if ($y < $equator) {
							$temp = ($y / $equator) * 29.0 + 2.0;
						} else {
							$temp = 27.0 - ($y - $equator) / $equator * 29.0;
						}
						if ($flow + $precipitation > (0.02616 * $temp * $temp + 0.2276 * $temp + 4.5227) / 3.0) {
							$map[$y][$x][Map::VEGETATION] = Map::VEGETATION_LAKE;
						} else {
							$map[$y][$x][Map::VEGETATION] = $precipitation < $config->fertile ? Map::VEGETATION_OASIS : Map::VEGETATION_MOOR;
						}
					} else {
						$altitude  = $map[$y][$x][Map::ALTITUDE];
						$neighbour = $map[$y + $direction[1]][$x + $direction[0]][Map::ALTITUDE];
						if ($flow / ($altitude - $neighbour) > $config->swamp) {
							$map[$y][$x][Map::VEGETATION] = $precipitation < $config->fertile ? Map::VEGETATION_OASIS : Map::VEGETATION_MOOR;
						}
					}
				}
			}
		}
	}

	private function minimumAltitude(int &$minimum, array $old, array $new, int $x, int $y, int $dx, int $dy): array {
		$altitude = $this->map[$y + $dy][$x + $dx][Map::ALTITUDE];
		if ($altitude < $minimum) {
			$minimum = $altitude;
			return $new;
		}
		return $old;
	}

	private function maximumAltitude(int $x, int $y, int $dx, int $dy, int $altitude): bool {
		$x   += $dx;
		$y   += $dy;
		if ($this->map[$y][$x][Map::BOOL] && $this->map[$y][$x][Map::ALTITUDE] > $altitude) {
			return true;
		}
		return false;
	}
}
