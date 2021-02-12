<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

use JetBrains\PhpStorm\Pure;

use Lemuria\Tools\Lemuria\Generator\Precipitation;
use Lemuria\Tools\Lemuria\Generator\Resources;
use Lemuria\Tools\Lemuria\Generator\Altitude;
use Lemuria\Tools\Lemuria\Generator\Vegetation;
use Lemuria\Tools\Lemuria\Generator\WaterFlow;

class MapGenerator
{
	use Altitude;
	use Precipitation;
	use Resources;
	use Vegetation;
	use WaterFlow;

	private array $stone = ['amount' => 10000, 'count' => 300, 'depth' => 0, 'd' => 10, 'a' => 5];

	private array $iron = ['amount' => 10000, 'count' => 300, 'depth' => 0, 'd' => 10, 'a' => 5];

	private array $mithril = ['amount' => 2500, 'count' => 21, 'depth' => -7, 'd' => 2, 'a' => 3];

	private array $quartz = ['amount' => 450, 'count' => 21, 'depth' => -4, 'd' => 2, 'a' => 3];

	private array $region = [
		Map::ALTITUDE      => MapConfig::OCEAN,
		Map::TYPE          => Terrain::OCEAN,
		Map::MOISTURE      => 0.0,
		Map::PRECIPITATION => 0.0,
		Map::POTENTIAL     => 0.0,
		Map::BOOL          => 0,
		Map::DIRECTION     => Direction::NONE,
		Map::FLOW          => 0.0,
		Map::VEGETATION    => Moisture::NONE
	];

	private array $map;

	#[Pure] public function __construct(private MapConfig $config) {
		$this->map = array_fill(0, $this->config->height, array_fill(0, $this->config->width, $this->region));
	}

	public function run(): self {
		$this->calculateTerrain($this->config, $this->map);
		$this->calculateClimate($this->config, $this->map);
		$this->calculateWaterFlow($this->config, $this->map);
		$this->calculateVegetation($this->config, $this->map);
		$this->calculateResources($this->config, $this->map);

		return $this;
	}

	public function load(array $map): void {
		$this->map = $map;
	}

	public function save(): array {
		return $this->map;
	}
}
