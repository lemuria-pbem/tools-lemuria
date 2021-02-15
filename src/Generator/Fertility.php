<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Generator;

use Lemuria\Tools\Lemuria\Area;
use Lemuria\Tools\Lemuria\Good;
use Lemuria\Tools\Lemuria\Land;
use Lemuria\Tools\Lemuria\Map;
use Lemuria\Tools\Lemuria\MapConfig;
use Lemuria\Tools\Lemuria\Moisture;
use Lemuria\Tools\Lemuria\Terrain;

/**
 * Wasserflächen als Anteil an der Gesamtfläche, die durch Wasser bedeckt ist. Süßwasserseen sind eher als
 * Seenlandschaft zu verstehen und nicht als ein einziger großer See, der genau an der Regionsgrenze endet.
 * Fertilität als Maßzahl wie viel verwertbare Biomasse erzeugt werden kann ... abhängig von Temperatur und
 * Wasserdargebot
 * Urbarkeit als Maßzahl, wie viel Prozent der Fläche für Äcker, Weiden oder Wälder genutzt werden kann
 *
 * 1) Bestimmung der Wasserflächen für Meeresufer, Oase, Sumpf, See = f(diverse)
 * 2) Bestimmung der Fertilität = f(Temp, NS, Abfluss)
 * 3) Bestimmung des Fischreichtums (Fisch) = f(Wasserflächen, Fertilität)
 * 4) Bestimmung der Urbarkeit (Gesamt - Wasserfläche)*f(Hoehe) unter Ausschluss von Gletscher und Ewigem Eis
 * 5) Zuteilung der urbaren Fläche auf Äcker, Weiden und Wälder
 *
 * interne Festlegungen
 * jeder Zug repräsentiert eine Woche
 * Regionsdurchmesser = 15 Meilen/Tag * 7 Tage = 105 Meilen (mit Straße 2 Regionen, also 30 Meilen/Tag) =>
 * Regionsgröße = 38.191 Quadratmeilen (noch immer sehr viel)
 * Ansätze: Agrarwirtschaft: 3,33 ha können eine Person erhalten // Weidewirtschaft: 15 ha / Person // Jagen und
 * Sammeln: 45 ha / Person // Fischen: Zugang zum Meer oder ein Fluss
 * Mit 100% Äckern könnten also 38.191*100/3,33 = 1.146.876 Personen von einer fruchtbaren Ebene ernährt werden.
 * Wenn die Bauern 10% (Zehent) mehr Land bewirtschaften, als sie selber brauchen, dann wären ca. 104.000 Personen als
 * Handwerker, Soldaten, Händler usw. einsetzbar. Da nähern wir uns dann den Werten von Fantasya an.
 * Die "Überproduktion" in der Landwirtschaft sollte vom Talentwert der Bauern abhängen. 5% je Talentstufe und relativ
 * schwierig zu ändern.
 *
 * Fertilität hat ein Optimum bei einer Temperatur von 30° und stagniert bei 0° (näherungsweise). Da ist dann der
 * Lichteinfall auch schon berücksichtigt. Wir haben Maximaltemperaturen von 27° im Programm.
 * Also machen wir als Fertilität einen linearen Relativwert der Temperatur Temp/30 * einem hyperbol. Relativwert des
 * NS (nach einigem Herumprobieren Wurzel(NS) / 2,64) gibt eine Einheitskurve
 * NS 0,5 => Fert = 0,27 / NS 4 => Fert. 0,75 / NS 7 => Fert 1,0
 * Bei Ozeanen hängt die Fertilität von der Wassertiefe ab ... Küstenshelf bis 50 m hat mehr Biomasse; Die Temperatur
 * spielt fast keine Rolle.
 */
trait Fertility
{
	private MapConfig $config;

	private array $map;

	private function calculateFertility(MapConfig $config, array &$map): void {
		if ($config->status[__FUNCTION__] ?? false) {
			return;
		}
		$this->config = $config;
		$this->map    =& $map;
		$minX         = $config->offsetX + 1;
		$maxX         = $config->maxX - 1;
		$minY         = $config->offsetY + 1;
		$maxY         = $config->maxY - 1;

		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				$altitude  = $map[$y][$x][Map::ALTITUDE];
				$land      = [Land::WATER => 0, Land::FIELD => 0, Land::PASTURE => 0, Land::FOREST => 0, Land::BUSH => 0];

				// Calculate water area.
				$vegetation = $map[$y][$x][Map::VEGETATION];
				if ($vegetation === Terrain::OCEAN) {
					$waterArea = 100;
				} elseif ($vegetation === Moisture::LAKE) {
					$waterArea              = rand(30, 70); // lakes have a random percentage of water
					$map[$y][$x][Map::FLOW] = 0.0; // prevent additional water in lake regions
				} else {
					$waterArea = 0;
					for ($dy = -1; $dy <= 1; $dy++) {
						for ($dx = -1; $dx <= 1; $dx++) {
							if ($map[$y + $dy][$x + $dx][Map::VEGETATION] === Terrain::OCEAN) {
								$waterArea += rand(3, 6); // additional water if neighbour us ocean
							}
						}
					}
					if ($vegetation === Moisture::MOOR) {
						$waterArea += 33; // additional water in swamps
					} elseif ($vegetation === Moisture::OASIS) {
						$waterArea += 10; // additional water in oases
					}
				}
				$water             = (int)round($waterArea / 100 * $this->config->square);
				$land[Land::WATER] = $water;

				// Calculate fertility.
				if ($vegetation === Terrain::OCEAN) {
					// in oceans fertility depends on water depth
					$fertility = 0.25 - 0.000000781 * $altitude * $altitude;
				} else {
					$precipitation = $map[$y][$x][Map::PRECIPITATION];
					$temperature   = $config->temperature()->forAltitude($y, $altitude);
					if ($precipitation <= 0.0) {
						$precipitation = $config->temperature()->toMoist($temperature);
					}
					if ($temperature < 0.0) {
						$fertility = 0.05 * sqrt($precipitation) / 2.64;
					} else {
						$fertility = $temperature / 27.0 * sqrt($precipitation) / 2.64;
						$fertility = $fertility + $map[$y][$x][Map::FLOW] / 250.0;
						if ($vegetation === Moisture::MOOR) {
							$fertility /= 2.0;
						}
					}
					if ($fertility > 1.0) {
						$fertility = 1.0;
					}
				}

				// Calculation of arable land..
				if ($vegetation === Terrain::OCEAN || $vegetation === Area::ICE || $vegetation === Area::GLACIER) {
					$arable = 0;
				} else {
					$arable = (int)round(($config->square - $water) * sqrt(($config->maxHeight - $altitude) / $config->maxHeight));
				}
				$remaining = $arable;

				// Calculation of land distribution.
				if ($vegetation === Area::RAIN_FOREST || $vegetation === Area::HIGH_FOREST || $vegetation === Area::RAIN_MOUNTAIN) {
					$forest = min($altitude / $config->maxHeight * rand(0, 100) + 60.0, 100.0);
				} else {
					$forest = ($altitude / $config->maxHeight * 100.0 - 5.0) * (rand(0, 100) / 100) + 5.0;
				}
				$forest             = (int)round($forest * $arable / 100.0);
				$remaining         -= $forest;
				$land[Land::FOREST] = $forest;

				$pasture             = (int)round($remaining * rand(0, 100) / 100.0);
				$remaining          -= $pasture;
				$land[Land::PASTURE] = $pasture;

    			$field = $temperature > 0.0 ? $remaining * rand(0, 100) / 100.0 : 0.0;
    			if ($vegetation === Area::DESERT || $vegetation === Area::HIGH_DESERT || $vegetation === Area::DESERT_MOUNTAIN) {
    				$desertField = $map[$y][$x][Map::FLOW] / 100.0 * $arable;
					if ($field > $desertField) {
						$field = $desertField;
					}
				}
    			$field             = (int)round($field);
    			$land[Land::FIELD] = $field;

				$bush             = $remaining - $field;
				$land[Land::BUSH] = $bush;

				// Calculation of goods.
				$fish = (int)round($water * $fertility / $config->hunting * 100.0);
				$crop = (int)round($field * $fertility / $config->farming * 100.0);
				$meat = (int)round($pasture * $fertility / $config->breeding * 100.0);
				$game = (int)round($bush * $fertility / $config->hunting * 100.0);
				$wood = (int)round($forest * $fertility / $config->forestry * 100.0);
				$good = [Good::FISH => $fish, Good::CROP => $crop, Good::MEAT => $meat, Good::GAME => $game, Good::WOOD => $wood];

				$map[$y][$x][Map::FERTILITY] = $fertility;
				$map[$y][$x][Map::ARABLE]    = $arable;
				$map[$y][$x][Map::LAND]      = $land;
				$map[$y][$x][Map::GOOD]      = $good;
			}
		}

		$config->status[__FUNCTION__] = true;
	}
}
