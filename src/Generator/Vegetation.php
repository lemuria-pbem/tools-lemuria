<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Generator;

use Lemuria\Tools\Lemuria\Map;
use Lemuria\Tools\Lemuria\MapConfig;

trait Vegetation
{
	private MapConfig $config;

	private array $map;

	public function getVegetationMap(): Map {
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
		return new Map($this->map, Map::VEGETATION);
	}

	private function calculateVegetation(MapConfig $config, array &$map): void {
		$this->config = $config;
		$this->map    =& $map;

		//TODO
	}
}
