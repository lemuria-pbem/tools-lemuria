<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

/**
 * Configuration for the map generator.
 *
 * Altitudes are measured in meters.
 */
final class MapConfig
{
	/**
	 * Altitude default for land mass creation.
	 */
	public const ZERO = -500;

	/**
	 * Altitude of ocean regions.
	 */
	public const OCEAN = self::ZERO + 100;

	/**
	 * Average deposit in center.
	 */
	public const DEPOSIT_SIZE = 0;

	/**
	 * Number of deposits.
	 */
	public const DEPOSIT_COUNT = 1;

	/**
	 * Average depth.
	 */
	public const DEPOSIT_DEPTH = 2;

	/**
	 * Depth spread (levels).
	 */
	public const DEPOSIT_SPREAD = 3;

	/**
	 * Deposit a-value for Gauss.
	 */
	public const DEPOSIT_A = 4;

	/**
	 * Start of land mass in the west.
	 */
	public int $offsetX = 0;

	/**
	 * Start of land mass in the south.
	 */
	public int $offsetY = 0;

	/**
	 * End of land mass in the east.
	 */
	public int $maxX = 50;

	/**
	 * End of land mass in the north.
	 */
	public int $maxY = 50;

	/**
	 * Total map width (west to east).
	 */
	public int $width = 50;

	/**
	 * Total map height (south to north), covering 90° S to 90° N.
	 */
	public int $height = 50;

	/**
	 * Number of random altitude seeds.
	 */
	public int $seeds = 100;

	/**
	 * Minimum initial seed altitude.
	 */
	public int $minHeight = 0;

	/**
	 * Maximum initial seed altitude.
	 */
	public int $maxHeight = 1100;

	/**
	 * Lower threshold for random altitude differences.
	 */
	public int $minDiff = -200;

	/**
	 * Upper threshold for random altitude differences.
	 */
	public int $maxDiff = 200;

	/**
	 * Maximum influence distance for altitude interpolation.
	 */
	public int $influence = 3;

	/**
	 * Minimum width of ocean border around land mass.
	 */
	public int $edge = 5;

	/**
	 * Minimum altitude of a plain region.
	 */
	public int $lowLand = 0;

	/**
	 * Minimum altitude of a highland region.
	 */
	public int $highland = 400;

	/**
	 * Minimum altitude of a mountain region.
	 */
	public int $mountain = 700;

	/**
	 * Temperature decrease per 100 meters.
	 */
	public float $hTemp = 1.0;

	/**
	 * Minimum precipitation of a desert.
	 */
	public float $desert = 0.0;

	/**
	 * Minimum precipitation of fertile land.
	 */
	public float $fertile = 0.4;

	/**
	 * Minimum precipitation of tropical region.
	 */
	public float $humid = 5.0;

	/**
	 * Minimum precipitation of very moist rain forest.
	 */
	public float $moist = 8.0;

	/**
	 * Minimum flow-per-slope ratio of a swamp/oasis region.
	 */
	public float $swamp = 0.07;

	/**
	 * Absolute area of a region in square kilometers.
	 */
	public int $square = 6000;

	/**
	 * Area per person for farming.
	 */
	public float $farming = 3.0;

	/**
	 * Area per person for breeding.
	 */
	public float $breeding = 15.0;

	/**
	 * Area per person for hunting.
	 */
	public float $hunting = 45.0;

	/**
	 * Area per tree for forestry.
	 */
	public float $forestry = 100.0;

	/*
	 * Definition of mineral resources.
	 */
	public array $resource = [
		Good::STONE => [
			self::DEPOSIT_SIZE  => 10000, self::DEPOSIT_COUNT => 5,
			self::DEPOSIT_DEPTH => 0, self::DEPOSIT_SPREAD => 10, self::DEPOSIT_A => 5
		],
		Good::QUARTZ => [
			self::DEPOSIT_SIZE  => 450, self::DEPOSIT_COUNT => 1,
			self::DEPOSIT_DEPTH => 4, self::DEPOSIT_SPREAD => 2, self::DEPOSIT_A => 3
		],
		Good::IRON => [
			self::DEPOSIT_SIZE  => 10000, self::DEPOSIT_COUNT => 5,
			self::DEPOSIT_DEPTH => 0, self::DEPOSIT_SPREAD => 10, self::DEPOSIT_A => 5
		],
		Good::MITHRIL => [
			self::DEPOSIT_SIZE  => 2500, self::DEPOSIT_COUNT => 1,
			self::DEPOSIT_DEPTH => 7, self::DEPOSIT_SPREAD => 2, self::DEPOSIT_A => 3
		]
	];

	/**
	 * Calculation status.
	 */
	public array $status = [];

	private static ?Temperature $temperature = null;

	public function temperature(): Temperature {
		if (!self::$temperature) {
			self::$temperature = new Temperature($this);
		}
		return self::$temperature;
	}

	public function load(array $config): void {
		foreach ($config as $name => $value) {
			$this->$name = $value;
		}
		self::$temperature = null;
	}

	public function save(): array {
		$data       = [];
		$reflection = new \ReflectionClass($this);
		foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
			$name        = $property->name;
			$data[$name] = $this->$name;
		}
		return $data;
	}
}
