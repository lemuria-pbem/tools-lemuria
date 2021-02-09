<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

final class MapConfig
{
	public const ZERO = -500;

	public const OCEAN = self::ZERO + 100;

	public int $offsetX = 0;

	public int $offsetY = 0;

	public int $maxX = 50;

	public int $maxY = 50;

	public int $width = 50;

	public int $height = 50;

	public int $seeds = 50;

	public int $minHeight = 0;

	public int $maxHeight = 1100;

	public int $minDiff = -200;

	public int $maxDiff = 200;

	public int $influence = 3;

	public int $edge = 5;

	public float $heTemp = 1.0;

	public int $lowLand = 0;

	public int $highland = 400;

	public int $mountain = 700;

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
