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
		return new Map($this->map, Map::VEGETATION);
	}

	private function calculateVegetation(MapConfig $config, array &$map): void {
		if ($config->status[__FUNCTION__] ?? false) {
			return;
		}
		$this->config = $config;
		$this->map    =& $map;

		//TODO
		$config->status[__FUNCTION__] = true;
	}
}
