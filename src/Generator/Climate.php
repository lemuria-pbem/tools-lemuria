<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Generator;

use Lemuria\Tools\Lemuria\MapConfig;

trait Climate
{
	private MapConfig $config;

	private array $map;

	private function calculateClimate(MapConfig $config, array &$map): void {
		$this->config = $config;
		$this->map    =& $map;

		//TODO
	}
}
