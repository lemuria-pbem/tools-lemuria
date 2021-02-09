<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Generator;

use Lemuria\Tools\Lemuria\MapConfig;

trait Resources
{
	private MapConfig $config;

	private array $map;

	private function calculateResources(MapConfig $config, array &$map): void {
		$this->config = $config;
		$this->map    =& $map;

		//TODO
	}
}
