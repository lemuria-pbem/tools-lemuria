<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Generator;

use function Lemuria\randInt;
use Lemuria\Tools\Lemuria\Map;
use Lemuria\Tools\Lemuria\MapConfig;

/**
 * Vorkommensknolle dreidimensional definieren
 * Zugrunde gelegt wird die Gauss'sche Normalverteilung in vertikaler und horizontaler Richtung. Es entsteht also ein
 * (eigentlich runde) Vorkommensknolle rund um zufällige Seeds.
 * Damit nicht bei jedem Rechengang die Normalverteilung mitgerechnet werden muss, geschieht dies für den
 * gewählten a-Wert einmal am Anfang.
 * Jede Höhenstufe sind 100m
 * Die Formeln sind auf einen a-Wert ausgelegt, der nicht mehr als 5 Felder streut.
 *
 * Gebirge sind zerklüftet. Die Lagerstätten aller Höhenstufen über 500m (H3-1) sind mit T1 zugänglich => Gebirge haben
 * größere Chancen auf oberflächennahe Lagerstätten
 * Hügelländer bieten die gesamten Rohstoffschichten zwischen H2 und H3 auf T1 an
 * Ebenen verbergen ihr Eisen unter 2 Schichten von taubem Gestein
 */
trait Resources
{
	/**
	 * @var array<int|string, array<int, array<int, float>>>
	 */
	private array $gauss = [];

	public function getResource(string $resource): Map {
		$map = new Map($this->config, $this->map, Map::RESOURCE);
		return $map->setResource($resource);
	}

	private function calculateResources(): void {
		$config = &$this->config;
		$map    = &$this->map;
		if ($config->status[__FUNCTION__] ?? false) {
			return;
		}

		$minX = $config->offsetX + 4;
		$maxX = $config->maxX - 5;
		$minY = $config->offsetY + 4;
		$maxY = $config->maxY - 5;
		$this->initGauss();

		foreach ($config->resource as $name => $resource) {
			$dSize   = $resource[MapConfig::DEPOSIT_SIZE];
			$dDepth  = -$resource[MapConfig::DEPOSIT_DEPTH];
			$dSpread = $resource[MapConfig::DEPOSIT_SPREAD];
			$dA      = $resource[MapConfig::DEPOSIT_A];

			// Calculate height.
			$depth0    = $dDepth - $dSpread - 5;
			$depthMax  = $dDepth + $dSpread + 5;
			$maxHeight = (int)ceil($config->maxHeight / 100);
			$maxZ      = $depthMax < $maxHeight? $maxHeight - $depth0 : $depthMax - $depth0;

			// Deposit cores.
			$deposit = array_fill(0, $config->height, array_fill(0, $config->width, array_fill(1, $maxZ, 0)));
			for ($i = 0; $i < $resource[MapConfig::DEPOSIT_COUNT]; $i++) {
				$x = randInt($minX, $maxX);
				$y = randInt($minY, $maxY);
				$z = randInt(0, 2 * $dSpread + 1) - $depth0 + $dDepth - $dSpread;
				if ($deposit[$y][$x][$z] >= 3000) {
					continue;
				}
				$altitude = $map[$y][$x][Map::ALTITUDE];
				$maxLevel = (int)ceil($altitude / 100) - $depth0;

				for ($a = -4; $a <= 4; $a++) {
					$aA = abs($a);
					for ($b = -4; $b <= 4; $b++) {
						$aB = abs($b);
						for ($c = -4; $c <= 4; $c++) {
							$zc = $z + $c;
							if ($zc > $maxLevel) {
								break; // No deposits above surface.
							}

							if ($a * $b < 0) {
								$dist = max($aA, $aB);
							} else {
								$dist = $aA + $aB;
							}
							if ($dist <= 4) {
								$d        = (int)round(0.7 * $dSize) + randInt(0, (int)round(0.4 * $dSize));
								$increase = (int)round($d * $this->gauss[$dA][$dist][abs($c)]);

								$deposit[$y + $b][$x + $a][$zc] += $increase;
							}
						}
					}
				}
			}

			// Adapt to terrain.
			$talentDeposit = array_fill(0, $config->height, array_fill(0, $config->width, array_fill(1, $maxZ, 0)));
			for ($y = $config->offsetY; $y < $config->maxX; $y++) {
				for ($x = $config->offsetX; $x < $config->maxY; $x++) {
					for ($z = 1; $z <= $maxZ; $z++) {
						$amount   = $deposit[$y][$x][$z];
						$altitude = $map[$y][$x][Map::ALTITUDE];
						if ($altitude >= $config->mountain) {
							$surface = (int)ceil($config->mountain / 100) - $depth0;
							if ($z >= $surface) {
								$talentDeposit[$y][$x][1] += 2 * $amount;
							} else {
								$talentDeposit[$y][$x][$surface + 1 - $z] = 2 * $amount;
							}
						} elseif ($altitude >= $config->highland) {
							$surface = (int)ceil($config->highland / 100) - $depth0;
							if ($z >= $surface) {
								$talentDeposit[$y][$x][1] += (int)round($amount / 2);
							} else {
								$talentDeposit[$y][$x][$surface + 1 - $z] = (int)round($amount / 2);
							}
						} elseif ($altitude >= $config->lowLand) {
							$surface = (int)ceil($altitude / 100) - $depth0;
							if ($z < $surface - 2) {
								$talentDeposit[$y][$x][$surface - $z + 1] = (int)round($amount / 2);
							}
						} else {
							$surface = (int)ceil($altitude / 100) - $depth0;
							if ($z < $surface - 3) {
								if ($surface - $z < 0) {
									$surface = $z;
								}
								$talentDeposit[$y][$x][$surface - $z] = (int)round($amount / 2);
							}
						}
					}
					$map[$y][$x][Map::RESOURCE][$name] = $talentDeposit[$y][$x];
				}
			}
		}

		$config->status[__FUNCTION__] = true;
	}

	private function initGauss(): void {
		$depositA = [];
		foreach ($this->config->resource as $resource) {
			$depositA[$resource[MapConfig::DEPOSIT_A]] = true;
		}
		$depositA = array_keys($depositA);
		sort($depositA);
		foreach ($depositA as $a) {
			for ($i = 0; $i <= 4; $i++) {
				for ($j = 0; $j <= 4; $j++) {
					$this->gauss[$a][$i][$j] = exp(-1.0 / $a * $i * $i) * exp(-1.0 / $a * $j * $j);
				}
			}
		}
	}
}
