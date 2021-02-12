<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Generator;

use Lemuria\Tools\Lemuria\Map;
use Lemuria\Tools\Lemuria\MapConfig;

trait Precipitation
{
	private function calculateClimate(MapConfig $config, array &$map): void {
		if ($config->status[__FUNCTION__] ?? false) {
			return;
		}
		$this->config = $config;
		$this->map    =& $map;
		$minX         = $config->offsetX;
		$maxX         = $config->maxX - 1;
		$minY         = $config->offsetY;
		$maxY         = $config->maxY;
		$equator      = $config->height / 2;
		$moistEast    = 0.0;

		// Humidity and rainfall from east to west.
		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $maxX; $x >= $minX; $x--) {
				if ($y < $equator) {
					$temp = ($y / $equator) * 29.0 - 2.0;
				} else {
					$temp = 27.0 - ($y - $equator) / $equator * 29.0;
				}
				$temp2 = $temp * $temp;

				if ($x === $maxX) {
					// Moisture absorption above ocean is 1/3.
					$moist = (0.02616 * $temp2 + 0.2276 * $temp + 4.5227) / 3.0;
				} else {
					$altitude = $map[$y][$x][Map::ALTITUDE];
					if ($altitude <= 0) {
						if ($moistEast + (0.02616 * $temp2 + 0.2276 * $temp + 4.5227) / 3.0 >= 0.02616 * $temp2 + 0.2276 * $temp + 4.5227) {
							$moist = 0.02616 * $temp2 + 0.2276 * $temp + 4.5227;
						} else {
							$moist = $moistEast + (0.02616 * $temp2 + 0.2276 * $temp + 4.5227) / 3.0;
						}
					} else {
						$precip = (0.1 + $altitude / 3000) * $moistEast;
						$moist  = $moistEast - $precip;

						$map[$y][$x][Map::PRECIPITATION] = $precip;
					}
				}

				$map[$y][$x][Map::MOISTURE] = $moist;
				$moistEast                  = $moist;
			}
		}

		// Iteration for more moisture in coast regions.
		$minX = $this->config->offsetX + $this->config->edge;
		$maxX = $this->config->maxX - $this->config->edge;
		$minY = $this->config->offsetY + $this->config->edge;
		$maxY = $this->config->maxY - $this->config->edge;
		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				$altitude = $map[$y][$x][Map::ALTITUDE];
				if ($altitude > 0) {
					$sum = 0.0;
					for ($dy = -1; $dy <= 1; $dy++) {
						for ($dx = -1; $dx <= 1; $dx++) {
							$sum += $map[$y + $dy][$x + $dx][Map::MOISTURE];
						}
					}
					$precip   = $map[$y][$x][Map::PRECIPITATION];
					$avgMoist = $sum / 7.0 + $precip;

					$map[$y][$x][Map::PRECIPITATION] = (0.1 + $altitude / 3000) * $avgMoist;
				}
			}
		}
		$config->status[__FUNCTION__] = true;
	}
}
