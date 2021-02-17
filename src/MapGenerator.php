<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

use JetBrains\PhpStorm\Pure;

use Lemuria\Tools\Lemuria\Generator\Altitude;
use Lemuria\Tools\Lemuria\Generator\Fertility;
use Lemuria\Tools\Lemuria\Generator\Precipitation;
use Lemuria\Tools\Lemuria\Generator\Resources;
use Lemuria\Tools\Lemuria\Generator\Vegetation;
use Lemuria\Tools\Lemuria\Generator\WaterFlow;

class MapGenerator
{
	use Altitude;
	use Fertility;
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
		Map::WATER         => Moisture::NONE,
		Map::VEGETATION    => Terrain::OCEAN,
		Map::FERTILITY     => 0.0,
		Map::ARABLE        => 0.0,
		Map::LAND          => null,
		Map::GOOD          => null,
		Map::RESOURCE      => []
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
		$this->calculateFertility($this->config, $this->map);
		$this->calculateResources($this->config, $this->map);

		return $this;
	}

	public function load(array $map): void {
		if (count($map) === $this->config->height) {
			foreach ($map as $y => $row) {
				if (count($row) === $this->config->width) {
					foreach ($row as $x => $values) {
						if (isset($this->map[$y][$x]) && isset($values[Map::ALTITUDE])) {
							$altitude = $values[Map::ALTITUDE];
							if (is_int($altitude)) {
								$this->map[$y][$x][Map::ALTITUDE] = $altitude;
							}
						}
					}
				}
			}
		}
	}

	public function save(): array {
		return $this->map;
	}
}
