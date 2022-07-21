<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Generator;

use function Lemuria\sign;
use Lemuria\Tools\Lemuria\Map;
use Lemuria\Tools\Lemuria\MapConfig;
use Lemuria\Tools\Lemuria\Terrain;

trait Altitude
{
	public function getTerrainMap(): Map {
		$minX = $this->config->offsetX + $this->config->edge;
		$maxX = $this->config->maxX - $this->config->edge;
		$minY = $this->config->offsetY + $this->config->edge;
		$maxY = $this->config->maxY - $this->config->edge;

		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				$altitude = $this->map[$y][$x][Map::ALTITUDE];
				$this->map[$y][$x][Map::TYPE] = match (true) {
					$altitude >= $this->config->mountain => Terrain::MOUNTAIN,
					$altitude >= $this->config->highland => Terrain::HIGHLAND,
					$altitude >= $this->config->lowLand  => Terrain::PLAIN,
					default                              => Terrain::OCEAN
				};
			}
		}
		return new Map($this->config, $this->map, Map::TYPE);
	}

	private function initSeeds(): void {
		$this->seeds = array_fill(0, $this->config->seeds, ['x' => 0, 'y' => 0, 'a' => 0]);
	}

	private function initMap(array $region): void {
		$this->map = array_fill(0, $this->config->height, array_fill(0, $this->config->width, $region));

		// Set regions of calculated map to ocean depth.
		$minX = $this->config->offsetX + $this->config->edge;
		$maxX = $this->config->maxX - $this->config->edge;
		$minY = $this->config->offsetY + $this->config->edge;
		$maxY = $this->config->maxY - $this->config->edge;
		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				$this->map[$y][$x][Map::ALTITUDE] = MapConfig::ZERO;
			}
		}
	}

	private function generateRandomSeeds(): void {
		$config = &$this->config;
		$map    = &$this->map;
		$seeds  = &$this->seeds;

		if (!($config->status[__FUNCTION__] ?? false)) {
			$minX  = $config->offsetX + $config->edge;
			$maxX  = $config->maxX - $config->edge;
			$minY  = $config->offsetY + $config->edge;
			$maxY  = $config->maxY - $config->edge;
			$order = [];
			for ($i = 0; $i < $config->seeds; $i++) {
				$x = rand($minX, $maxX);
				$y = rand($minY, $maxY);
				if (isset($order[$y][$x])) {
					$i--;
					continue;
				}
				$order[$y][$x] = rand($config->minHeight, $config->maxHeight);
			}

			$i = 0;
			ksort($order);
			foreach ($order as $y => $altitudes) {
				ksort($altitudes);
				foreach ($altitudes as $x => $altitude) {
					$seeds[$i]['x'] = $x;
					$seeds[$i]['y'] = $y;
					$seeds[$i]['a'] = $altitude;
					$i++;
				}
			}
		}

		for ($i = 0; $i < $this->config->seeds; $i++) {
			$x                          = $seeds[$i]['x'];
			$y                          = $seeds[$i]['y'];
			$altitude                   = $seeds[$i]['a'];
			$map[$y][$x][Map::ALTITUDE] = $altitude;
		}

		$config->status[__FUNCTION__] = true;
	}

	private function calculateTerrain(): void {
		$config = &$this->config;
		$map    = &$this->map;
		$seeds  = &$this->seeds;
		if ($config->status[__FUNCTION__] ?? false) {
			return;
		}

		$minX = $config->offsetX + $config->edge;
		$maxX = $config->maxX - $config->edge;
		$minY = $config->offsetY + $config->edge;
		$maxY = $config->maxY - $config->edge;

		// First ring around seeds.
		for ($i = 0; $i < $config->seeds; $i++) {
			$x = $seeds[$i]['x'];
			$y = $seeds[$i]['y'];
			$this->interpolate($x, $y, 0, 1);
			$this->interpolate($x, $y, 1, 0);
			$this->interpolate($x, $y, 1, -1);
			$this->interpolate($x, $y, 0, -1);
			$this->interpolate($x, $y, -1, 0);
			$this->interpolate($x, $y, -1, 1);
		}

		// Second ring around seeds.
		for ($i = 0; $i < $config->seeds; $i++) {
			$x = $seeds[$i]['x'];
			$y = $seeds[$i]['y'];
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

		$config->status[__FUNCTION__] = true;
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
		if ($altitude > $this->config->maxHeight) {
			$altitude = $this->config->maxHeight;
		}

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
		if ($altitude > $this->config->maxHeight) {
			$altitude = $this->config->maxHeight;
		}

		$this->map[$y][$x][Map::ALTITUDE] = $altitude;
	}
}
