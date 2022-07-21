<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Generator;

use Lemuria\Tools\Lemuria\Area;
use Lemuria\Tools\Lemuria\Map;
use Lemuria\Tools\Lemuria\Moisture;
use Lemuria\Tools\Lemuria\Terrain;

trait Vegetation
{
	public function getVegetationMap(): Map {
		return new Map($this->config, $this->map, Map::VEGETATION);
	}

	private function calculateVegetation(): void {
		$config = &$this->config;
		$map    = &$this->map;
		if ($config->status[__FUNCTION__] ?? false) {
			return;
		}

		$minX = $config->offsetX;
		$maxX = $config->maxX;
		$minY = $config->offsetY;
		$maxY = $config->maxY;

		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				$altitude = $map[$y][$x][Map::ALTITUDE];
				$temp     = $config->temperature()->forAltitude($y, $altitude);
				if ($temp < 0) {
					$vegetation = match (true) {
						$altitude < $config->lowLand  => Area::ICE,
						$altitude < $config->mountain => Area::TUNDRA,
						default                       => Area::GLACIER
					};
				} else {
					$water = $map[$y][$x][Map::WATER];
					if ($water > Moisture::NONE && $water <= Moisture::OASIS) {
						$vegetation = $water;
					} else {
						$precipitation = $map[$y][$x][Map::PRECIPITATION];
						if ($altitude < $config->lowLand) {
							$vegetation = Terrain::OCEAN;
						} elseif ($altitude < $config->highland) {
							$vegetation = match (true) {
								$precipitation < $config->fertile => Area::DESERT,
								$precipitation < $config->humid   => Terrain::PLAIN,
								default                           => Area::RAIN_FOREST
							};
						} elseif ($altitude < $config->mountain) {
							$vegetation = match (true) {
								$precipitation < $config->fertile => Area::HIGH_DESERT,
								$precipitation < $config->humid   => Terrain::HIGHLAND,
								default                           => Area::HIGH_FOREST
							};
						} else {
							$vegetation = match (true) {
								$precipitation < $config->fertile => Area::DESERT_MOUNTAIN,
								$precipitation < $config->humid   => Terrain::MOUNTAIN,
								default                           => Area::RAIN_MOUNTAIN
							};
						}
					}
				}
				$map[$y][$x][Map::VEGETATION] = $vegetation;
			}
		}

		$config->status[__FUNCTION__] = true;
	}
}
