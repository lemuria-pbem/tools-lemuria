<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

use JetBrains\PhpStorm\Pure;

use Lemuria\Tools\Lemuria\Generator\Climate;
use Lemuria\Tools\Lemuria\Generator\Resources;
use Lemuria\Tools\Lemuria\Generator\Terrain;
use Lemuria\Tools\Lemuria\Generator\Vegetation;
use Lemuria\Tools\Lemuria\Generator\WaterFlow;

class MapGenerator
{
	use Climate;
	use Resources;
	use Terrain;
	use Vegetation;
	use WaterFlow;

	private array $stone = ['amount' => 10000, 'count' => 300, 'depth' => 0, 'd' => 10, 'a' => 5];

	private array $iron = ['amount' => 10000, 'count' => 300, 'depth' => 0, 'd' => 10, 'a' => 5];

	private array $mithril = ['amount' => 2500, 'count' => 21, 'depth' => -7, 'd' => 2, 'a' => 3];

	private array $quartz = ['amount' => 450, 'count' => 21, 'depth' => -4, 'd' => 2, 'a' => 3];

	private array $region = [
		Map::ALTITUDE => MapConfig::OCEAN, Map::TYPE => 0
	];

	private MapConfig $config;

	private array $map;

	#[Pure] public function __construct() {
		$this->config = new MapConfig();
		$this->map    = array_fill(0, $this->config->height, array_fill(0, $this->config->width, $this->region));
	}

	public function run(): self {
		$this->calculateTerrain($this->config, $this->map);
		$this->calculateClimate($this->config, $this->map);
		$this->calculateWaterFlow($this->config, $this->map);
		$this->calculateVegetation($this->config, $this->map);
		$this->calculateResources($this->config, $this->map);

		return $this;
	}

	public function getMap(): Map {
		$minX = $this->config->offsetX + $this->config->edge;
		$maxX = $this->config->maxX - $this->config->edge;
		$minY = $this->config->offsetY + $this->config->edge;
		$maxY = $this->config->maxY - $this->config->edge;

		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				$altitude = $this->map[$y][$x][Map::ALTITUDE];
				$this->map[$y][$x][Map::TYPE] = match (true) {
					$altitude >= $this->config->mountain => 3,
					$altitude >= $this->config->highland => 2,
					$altitude >= $this->config->lowLand => 1,
					default => 0
				};
			}
		}
		return new Map($this->map);
	}
}