<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Generator;

use function Lemuria\sign;

use Lemuria\Tools\Lemuria\Map;
use Lemuria\Tools\Lemuria\MapConfig;

trait Terrain
{
	private MapConfig $config;

	private array $map;

	public function getTerrainMap(): Map {
		$minX = $this->config->offsetX + $this->config->edge;
		$maxX = $this->config->maxX - $this->config->edge;
		$minY = $this->config->offsetY + $this->config->edge;
		$maxY = $this->config->maxY - $this->config->edge;

		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				$altitude = $this->map[$y][$x][Map::ALTITUDE];
				$this->map[$y][$x][Map::TYPE] = match (true) {
					$altitude >= $this->config->mountain => Map::TERRAIN_MOUNTAIN,
					$altitude >= $this->config->highland => Map::TERRAIN_HIGHLAND,
					$altitude >= $this->config->lowLand  => Map::TERRAIN_PLAIN,
					default                              => Map::TERRAIN_OCEAN
				};
			}
		}
		return new Map($this->map);
	}

	private function calculateTerrain(MapConfig $config, array &$map): void {
		$this->config = $config;
		$this->map    =& $map;

		$minX  = $config->offsetX + $config->edge;
		$maxX  = $config->maxX - $config->edge;
		$minY  = $config->offsetY + $config->edge;
		$maxY  = $config->maxY - $config->edge;
		$seedX = array_fill(0, $config->seeds, 0);
		$seedY = array_fill(0, $config->seeds, 0);

		// Set regions of calculated map to ocean depth.
		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				$map[$y][$x][Map::ALTITUDE] = MapConfig::ZERO;
			}
		}

		// Calculate random seeds.
		for ($i = 0; $i < $config->seeds; $i++) {
			$x                             = rand($minX, $maxX);
			$y                             = rand($minY, $maxY);
			$altitude                      = rand($config->minHeight, $config->maxHeight);
			$seedX[$i]                        = $x;
			$seedY[$i]                        = $y;
			$map[$y][$x][Map::ALTITUDE] = $altitude;
		}

		// First ring around seeds.
		for ($i = 0; $i < $config->seeds; $i++) {
			$x = $seedX[$i];
			$y = $seedY[$i];
			$this->interpolate($x, $y, 0, 1);
			$this->interpolate($x, $y, 1, 0);
			$this->interpolate($x, $y, 1, -1);
			$this->interpolate($x, $y, 0, -1);
			$this->interpolate($x, $y, -1, 0);
			$this->interpolate($x, $y, -1, 1);
		}

		// Second ring around seeds.
		for ($i = 0; $i < $config->seeds; $i++) {
			$x = $seedX[$i];
			$y = $seedY[$i];
			$this->interpolate($x, $y, 0, 2);
			$this->interpolate($x, $y, 2, 0);
			$this->interpolate($x, $y, 2, -2);
			$this->interpolate($x, $y, 0, -2);
			$this->interpolate($x, $y, -2, 0);
			$this->interpolate($x, $y, -2, 2);
			$this->insert($x, $y, 1, 1, 0, 1, 1, 0, 1, -1, 0, -1, -1, 0, -1, 1);
			$this->insert($x, $y, 2, -1, 1, 0, 1, -1, 0, -1, -1, 0, -1, 1, 0, 1);
			$this->insert($x, $y, 1, -2, 1, -1, 0, -1, -1, 0, -1, 1, 0, 1, 1, 0);
			$this->insert($x, $y, -1, -1, 0, -1, -1, 0, -1, 1, 0, 1, 1, 0, 1, -1);
			$this->insert($x, $y, -2, 1, -1, 0, -1, 1, 0, 1, 1, 0, 1, -1, 0, -1);
			$this->insert($x, $y, -1, 2, -1, 1, 0, 1, 1, 0, 1, -1, 0, -1, -1, 0);
		}

		// Set ocean regions where only water exists in a two-region distance.
		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				if ($map[$y + 1][$x][Map::ALTITUDE] > MapConfig::OCEAN || $map[$y + 2][$x][Map::ALTITUDE] > MapConfig::OCEAN) {
					continue;
				}
				if ($map[$y][$x + 1][Map::ALTITUDE] > MapConfig::OCEAN || $map[$y][$x + 2][Map::ALTITUDE] > MapConfig::OCEAN) {
					continue;
				}
				if ($map[$y - 1][$x + 1][Map::ALTITUDE] > MapConfig::OCEAN || $map[$y - 2][$x + 2][Map::ALTITUDE] > MapConfig::OCEAN) {
					continue;
				}
				if ($map[$y - 1][$x][Map::ALTITUDE] > MapConfig::OCEAN || $map[$y - 2][$x][Map::ALTITUDE] > MapConfig::OCEAN) {
					continue;
				}
				if ($map[$y][$x - 1][Map::ALTITUDE] > MapConfig::OCEAN || $map[$y][$x - 2][Map::ALTITUDE] > MapConfig::OCEAN) {
					continue;
				}
				if ($map[$y + 1][$x - 1][Map::ALTITUDE] > MapConfig::OCEAN || $map[$y + 2][$x - 2][Map::ALTITUDE] > MapConfig::OCEAN) {
					continue;
				}
				$map[$y][$x][Map::ALTITUDE] = MapConfig::OCEAN;
			}
		}

		// Interpolate remaining regions that have not been set.
		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				if ($map[$y][$x][Map::ALTITUDE] <= MapConfig::ZERO) {
					$altitude = 0;
					$count    = 0;
					for ($dy = -1; $dy <= 1; $dy++) {
						for ($dx = -1; $dx <= 1; $dx++) {
							if (abs($dx + $dy) !== 2) {
								$alt = $map[$y + $dy][$x + $dx][Map::ALTITUDE];
								if ($alt > MapConfig::ZERO) {
									$altitude += $alt;
									$count++;
								}
							}
						}
					}
					if ($count) {
						$map[$y][$x][Map::ALTITUDE] = (int)round($altitude / $count) + rand($config->minDiff, $config->maxDiff);
					}
				}
			}
		}
	}

	private function interpolate(int $sx, int $sy, int $dx, int $dy): void {
		$x        = $sx + $dx;
		$y        = $sy + $dy;
		$altitude = $this->map[$y][$x][Map::ALTITUDE];
		if ($altitude > MapConfig::OCEAN) {
			return;
		}

		$dist     = max(abs($dx), abs($dy)) - 1;
		$dxs      = $dx === 0 ? 0 : sign($dx);
		$dys      = $dy === 0 ? 0 : sign($dy);
		$altX     = $sx + $dist * $dxs;
		$altY     = $sy + $dist * $dys;
		$altitude = $this->map[$altY][$altX][Map::ALTITUDE];
		$altDelta = 0;
		for ($i = 1; $i <= $this->config->influence; $i++) {
			$distX   = $x + $i * $dxs;
			$distY   = $y + $i * $dys;
			$distAlt = $this->map[$distY][$distX][Map::ALTITUDE];
			if ($distAlt !== MapConfig::ZERO) {
				$altDelta = (int)(0.5 ** $i * ($altitude - $distAlt));
				break;
			}
		}
		$altitude = $altitude + rand($this->config->minDiff, $this->config->maxDiff) - $altDelta;

		$this->map[$y][$x][Map::ALTITUDE] = $altitude;
	}

	private function insert(int $x0, int $y0, int $sx, int $sy, int $gx, int $gy, int $hx, int $hy,
							int $x1, int $y1, int $x2, int $y2, int $x3, int $y3, int $x4, int $y4): void {
		$x        = $x0 + $sx;
		$y        = $y0 + $sy;
		$altitude = $this->map[$y][$x][Map::ALTITUDE];
		if ($altitude > MapConfig::OCEAN) {
			return;
		}

		$altDelta = 0;
		$count    = 4;
		$altitude = $this->map[$y + $gy][$x + $gx][Map::ALTITUDE];
		if ($altitude > MapConfig::ZERO) {
			$altDelta = $altitude;
			$count++;
		}
		$altitude = $this->map[$y + $hy][$x + $hx][Map::ALTITUDE];
		if ($altitude > MapConfig::ZERO) {
			$altDelta += $altitude;
			$count++;
		}
		$altitude = $this->map[$y + $y1][$x + $x1][Map::ALTITUDE] + $this->map[$y + $y2][$x + $x2][Map::ALTITUDE]
					+ $this->map[$y + $y3][$x + $x3][Map::ALTITUDE] + $this->map[$y + $y4][$x + $x4][Map::ALTITUDE];
		$altitude = (int)round(($altitude + $altDelta) / $count) + rand($this->config->minDiff, $this->config->maxDiff);

		$this->map[$y][$x][Map::ALTITUDE] = $altitude;
	}
}
