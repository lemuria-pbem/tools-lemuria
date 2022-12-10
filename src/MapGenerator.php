<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

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

	private array $seeds;

	public function __construct(private readonly MapConfig $config) {
		$this->initSeeds();
		$this->initMap($this->region);
	}

	public function run(): self {
		$this->generateRandomSeeds();
		$this->calculateTerrain();
		$this->calculateClimate();
		$this->calculateWaterFlow();
		$this->calculateVegetation();
		$this->calculateFertility();
		$this->calculateResources();

		return $this;
	}

	public function getSeeds(): array {
		return $this->seeds;
	}

	public function setSeeds(array $seeds): void {
		$this->seeds = $seeds;
	}

	public function load(array $map): void {
		$this->map = $map;
	}

	public function save(): array {
		return $this->map;
	}
}
