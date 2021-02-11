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
	 * Maximm influence distance for altitude interpolation.
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

	public float $heTemp = 1.0;

	public float $desert = 0.0;

	public float $fertile = 0.4;

	public float $humid = 5.0;

	public float $moist = 8.0;

	public float $swamp = 0.07;

	public int $square = 38191;

	public float $farming = 3.33;

	public float $breeding = 15.0;

	public float $hunting = 45.0;
}
