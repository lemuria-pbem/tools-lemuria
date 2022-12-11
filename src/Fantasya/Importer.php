<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Fantasya;

use JetBrains\PhpStorm\Pure;

use function Lemuria\getClass;
use Lemuria\Exception\EntitySetException;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Ability;
use Lemuria\Model\Fantasya\Building\Castle;
use Lemuria\Model\Fantasya\Commodity\Peasant;
use Lemuria\Model\Fantasya\Commodity\Silver;
use Lemuria\Model\Fantasya\Commodity\Wood;
use Lemuria\Model\Fantasya\Construction;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Luxuries;
use Lemuria\Model\Fantasya\Luxury;
use Lemuria\Model\Fantasya\Offer;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Quantity;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Relation;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Model\Fantasya\Vessel;
use Lemuria\Model\World\OctagonalMap;

class Importer
{
	use BuilderTrait;

	private \PDO $database;

	private Converter $converter;

	private array $map;

	private int $minX;

	private int $minY;

	private int $xOffset;

	private int $yOffset;

	private int $xFactor;

	private array $regions = [];

	private array $luxuries = [];

	private array $constructions = [];

	private array $units = [];

	private array $parties = [];

	private array $vessels = [];

	private array $invalidConstructions = [];

	private array $owners = [];

	private array $captains = [];

	private int $world = 1;

	#[Pure] public function __construct(\PDO $database) {
		$this->database  = $database;
		$this->converter = new Converter();
	}

	public function import(): Importer {
		$this->importMap();
		$this->importRegions();
		$this->importConstructions();
		$this->importVessels();
		$this->importParties();
		$this->importUnits();

		return $this;
	}

	public function save(string $storage): Importer {
		Lemuria::Log()->debug('Saving JSON files...');
		if (!is_dir($storage)) {
			mkdir($storage, 0755, true);
		}
		$this->saveAll($storage);

		return $this;
	}

	private function importMap(): void {
		Lemuria::Log()->debug('Importing map...');
		$stmt = $this->database->query("SELECT MIN(koordx), MAX(koordx), MIN(koordy), MAX(koordy) FROM regionen WHERE welt = " . $this->world);
		$stmt->execute();
		$rows = $stmt->fetchAll(\PDO::FETCH_NUM);
		if (!isset($rows[0]) || count($rows[0]) !== 4) {
			throw new \RuntimeException('Could not get map size.');
		}
		$minX      = (int)$rows[0][0];
		$xOffset   = -$minX;
		$minX     += $xOffset;
		$maxX      = (int)$rows[0][1] + $xOffset;
		$minY      = (int)$rows[0][2];
		$yOffset   = -$minY;
		$minY     += $yOffset;
		$maxY      = (int)$rows[0][3] + $yOffset;
		$xFactor   = (int)pow(10, ceil(log10($maxX + 1)));
		$xKeys     = array_fill($minX, $maxX - $minX + 1, null);
		$yKeys     = array_fill($minY, $maxY - $minY + 1, null);
		$this->map = array_fill_keys(array_keys($yKeys), $xKeys);
		if (false) {
			echo 'Map size: x[' . $minX . ', ' . $maxX . '] y[' . $minY . ', ' . $maxY . ']  x-Faktor: ' . $xFactor . PHP_EOL;
		}

		$this->minX    = $minX;
		$this->minY    = $minY;
		$this->xOffset = $xOffset;
		$this->yOffset = $yOffset;
		$this->xFactor = $xFactor;
	}

	private function importRegions(): void {
		Lemuria::Log()->debug('Importing regions...');
		$sql  = "SELECT koordx, koordy, bauern, silber, name, Beschreibung, typ FROM regionen WHERE welt = " . $this->world . " ORDER BY koordy, koordx";
		$stmt = $this->database->query($sql);
		$stmt->execute();
		foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $regionRow) {
			$kx          = (int)$regionRow['koordx'];
			$ky          = (int)$regionRow['koordy'];
			$x           = $kx + $this->xOffset;
			$y           = $ky + $this->yOffset;
			$name        = $regionRow['name'];
			$description = $regionRow['Beschreibung'];
			$typ         = $regionRow['typ'];
			$bauern      = (int)$regionRow['bauern'];
			$silver      = (int)$regionRow['silber'];
			$food        = (int)floor($silver / 10);

			$id     = $y * $this->xFactor + $x;
			$region = new Region();
			$region->setId(new Id($id));
			$region->setName($name ?? '');
			$region->setDescription($description ?? '');
			$landscape = $this->converter->landscape($typ);
			$region->setLandscape(self::createLandscape($landscape));
			if ($typ != 'Ozean') {
				if ($bauern > 0) {
					$region->Resources()->add(new Quantity(self::createCommodity(Peasant::class), $bauern));
				}
				if ($silver > 0) {
					$region->Resources()->add(new Quantity(self::createCommodity(Silver::class), $silver));
				}
				if ($food > 0) {
					//$region->Resources()->add(new Quantity(self::createCommodity(Food::class), $food));
				}

				$sql  = "SELECT anzahl, resource FROM resourcen WHERE koordx = " . $kx . " AND koordy = " . $ky . " AND welt = " . $this->world;
				$stmt = $this->database->query($sql);
				$stmt->execute();
				foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $resourceRow) {
					$amount   = (int)$resourceRow['anzahl'];
					$resource = $resourceRow['resource'];
					if ($amount > 0) {
						$resource = $this->converter->resource($resource);
						if ($resource) {
							$region->Resources()->add(new Quantity(self::createCommodity($resource), $amount));
						}
					}
				}

				$sql  = "SELECT luxus, nachfrage FROM luxus WHERE koordx = " . $kx . " AND koordy = " . $ky . " AND welt = " . $this->world . " ORDER BY nachfrage";
				$stmt = $this->database->query($sql);
				$stmt->execute();
				$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				if (!empty($rows)) {
					if (count($rows) !== 8) {
						throw new \RuntimeException('Luxuries per region must consist of 8 rows.');
					}
					$class     = $this->converter->commodity($rows[0]['luxus']);
					$nachfrage = (int)$rows[0]['nachfrage'];
					$luxury    = self::createCommodity($class); /* @var Luxury $luxury */
					$price     = $this->converter->demand($luxury, $nachfrage);
					$luxuries  = new Luxuries(new Offer($luxury, $price));
					for ($i = 1; $i < 8; $i++) {
						$class     = $this->converter->luxury($rows[$i]['luxus']);
						$nachfrage = (int)$rows[$i]['nachfrage'];
						$luxury    = self::createCommodity($class); /* @var Luxury $luxury */
						$price     = $this->converter->demand($luxury, $nachfrage);
						$luxuries->offsetGet($class)->setPrice($price);
					}
					$this->luxuries[$id] = $luxuries;
				}
			}

			$this->regions[$id] = $region;
			$this->map[$y][$x]  = $id;
		}
	}

	private function importConstructions(): void {
		Lemuria::Log()->debug('Importing constructions...');
		$sql  = "SELECT nummer, koordx, koordy, type, size, name, beschreibung, owner FROM gebaeude WHERE welt = " . $this->world . " ORDER BY nummer";
		$stmt = $this->database->query($sql);
		$stmt->execute();
		foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$id          = (int)$row['nummer'];
			$x           = (int)$row['koordx'] + $this->xOffset;
			$y           = (int)$row['koordy'] + $this->yOffset;
			$type        = $row['type'];
			$size        = (int)$row['size'];
			$name        = $row['name'];
			$description = $row['beschreibung'];
			$owner       = (int)$row['owner'];

			$building = $this->converter->building($type, $size);
			if ($building) {
				$rid = $y * $this->xFactor + $x;
				if (!isset($this->regions[$rid])) {
					throw new \RuntimeException('Invalid region ' . $rid . '.');
				}
				/* @var Region $region */
				$region = $this->regions[$rid];

				$construction = new Construction();
				$construction->setId(new Id($id));
				$construction->setName($name ?? '');
				$construction->setDescription($description ?? '');
				$construction->setBuilding(self::createBuilding($building));
				$construction->setSize($size);

				$this->constructions[$id] = $construction;
				$region->Estate()->add($construction);

				if ($owner > 0) {
					$this->owners[$id] = $owner;
				}

				if ($construction->Building() instanceof Castle && $size >= Castle::MARKET_SIZE) {
					if (isset($this->luxuries[$rid])) {
						$region->setLuxuries($this->luxuries[$rid]);
					}
				}
			} else {
				$this->invalidConstructions[$id] = true;
			}
		}
	}

	private function importVessels(): void {
		Lemuria::Log()->debug('Importing vessels...');
		$sql  = "SELECT nummer, koordx, koordy, type, name, beschreibung, kapitaen, groesse, fertig, kueste FROM schiffe WHERE welt = " . $this->world . " ORDER BY nummer";
		$stmt = $this->database->query($sql);
		$stmt->execute();
		foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$id          = (int)$row['nummer'];
			$x           = (int)$row['koordx'] + $this->xOffset;
			$y           = (int)$row['koordy'] + $this->yOffset;
			$type        = $row['type'];
			$name        = $row['name'];
			$description = $row['beschreibung'];
			$kapitaen    = (int)$row['kapitaen'];
			$groesse     = (int)$row['groesse'];
			$fertig      = (bool)$row['fertig'];
			$kueste      = $this->converter->anchor($row['kueste']);

			$rid = $y * $this->xFactor + $x;
			if (!isset($this->regions[$rid])) {
				throw new \RuntimeException('Invalid region ' . $rid . '.');
			}
			/* @var Region $region */
			$region     = $this->regions[$rid];
			$ship       = self::createShip($this->converter->ship($type));
			$material   = $ship->getMaterial()->offsetGet(Wood::class)->Count();
			$completion = (float)($groesse / $material);

			$vessel = new Vessel();
			$vessel->setId(new Id($id));
			$vessel->setName($name ?? '');
			$vessel->setDescription($description ?? '');
			$vessel->setShip($ship);
			$vessel->setCompletion($completion);
			$vessel->setAnchor($kueste);

			$this->vessels[$id] = $vessel;
			$region->Fleet()->add($vessel);

			if ($kapitaen > 0) {
				$this->captains[$id] = $kapitaen;
			}
		}
	}

	private function importParties(): void {
		Lemuria::Log()->debug('Importing parties...');
		$sql  = "SELECT name, beschreibung, rasse, originx, originy, id FROM partei WHERE monster = 0 ORDER BY id";
		$stmt = $this->database->query($sql);
		$stmt->execute();
		foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$id          = $row['id'];
			$x           = (int)$row['originx'] + $this->xOffset;
			$y           = (int)$row['originy'] + $this->yOffset;
			$race        = $row['rasse'];
			$name        = $row['name'];
			$description = $row['beschreibung'];

			$rid = $y * $this->xFactor + $x;
			if (!isset($this->regions[$rid])) {
				throw new \RuntimeException('Invalid region ' . $rid . '.');
			}
			/* @var Region $region */
			$region = $this->regions[$rid];

			$party = new Party();
			$party->setId(Id::fromId($id));
			$party->setName($name ?? '');
			$party->setDescription($description ?? '');
			$party->setRace(self::createRace($this->converter->race($race)));
			$party->setOrigin($region);

			$this->parties[$party->Id()->Id()] = $party;
		}

		$sql  = "SELECT partei, partner, optionen FROM allianzen";
		$stmt = $this->database->query($sql);
		$stmt->execute();
		foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$p1     = (int)$row['partei'];
			$p2     = (int)$row['partner'];
			$option = $row['optionen'];
			if (!isset($this->parties[$p1]) || !isset($this->parties[$p2])) {
				throw new \RuntimeException('Error in alliance between ' . $p1 . ' and ' . $p2 . '.');
			}
			$party    = $this->parties[$p1]; /* @var Party $party */
			$partner  = $this->parties[$p2]; /* @var Party $partner */
			$relation = new Relation($partner);
			$relation->set($this->converter->agreement($option));
			$party->Diplomacy()->add($relation);
		}
	}

	private function importUnits(): void {
		Lemuria::Log()->debug('Importing units...');
		$sql  = "SELECT nummer, koordx, koordy, person, rasse, partei, name, beschreibung, gebaeude, schiff, bewacht, kampfposition FROM einheiten WHERE welt = " . $this->world . " ORDER BY nummer";
		$stmt = $this->database->query($sql);
		$stmt->execute();
		foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$id          = (int)$row['nummer'];
			$x           = (int)$row['koordx'] + $this->xOffset;
			$y           = (int)$row['koordy'] + $this->yOffset;
			$race        = $row['rasse'];
			$pid         = (int)$row['partei'];
			$size        = (int)$row['person'];
			$name        = $row['name'];
			$description = $row['beschreibung'];
			$cid         = (int)$row['gebaeude'];
			$vid         = (int)$row['schiff'];
			$isGuarding  = (bool)$row['bewacht'];
			$battleRow   = $row['kampfposition'];

			$race = $this->converter->race($race);
			if ($race) {
				$rid = $y * $this->xFactor + $x;
				if (!isset($this->regions[$rid])) {
					throw new \RuntimeException('Invalid region ' . $rid . ' for unit ' . $id . '.');
				}
				/* @var Region $region */
				$region = $this->regions[$rid];

				if (!isset($this->parties[$pid])) {
					throw new \RuntimeException('Invalid party ' . $pid . ' for unit ' . $id . '.');
				}
				/* @var Party $party */
				$party = $this->parties[$pid];

				if ($cid > 0 && $vid > 0) {
					$cid = 0;
					Lemuria::Log()->warning('Unit ' . $id . ' cannot be in construction and vessel at the same time, removing from construction.');
				}
				if ($cid > 0) {
					if (isset($this->invalidConstructions[$cid])) {
						$cid = 0;
					} else {
						if (!isset($this->constructions[$cid])) {
							throw new \RuntimeException('Invalid construction ' . $cid . ' for unit ' . $id . '.');
						}
						/* @var Construction $construction */
						$construction = $this->constructions[$cid];
					}
				}
				if ($vid > 0) {
					if (!isset($this->vessels[$vid])) {
						throw new \RuntimeException('Invalid vessel ' . $vid . ' for unit ' . $id . '.');
					}
					/* @var Vessel $vessel */
					$vessel = $this->vessels[$vid];
				}

				$unit = new Unit();
				$unit->setId(new Id($id));
				$unit->setName($name ?? '');
				$unit->setDescription($description ?? '');
				$unit->setRace(self::createRace($race));
				$unit->setSize($size);
				$unit->setIsGuarding($isGuarding);
				$unit->setBattleRow($this->converter->battleRow($battleRow));

				$this->units[$id] = $unit;
				$party->People()->add($unit);
				$region->Residents()->add($unit);
				if ($cid > 0) {
					$construction->Inhabitants()->add($unit);
				} elseif ($vid > 0) {
					$vessel->Passengers()->add($unit);
				}

				$sql  = "SELECT talent, lerntage FROM skills WHERE nummer = " . $id;
				$stmt = $this->database->query($sql);
				$stmt->execute();
				foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $skillRow) {
					$skill      = $skillRow['talent'];
					$lerntage   = (int)$skillRow['lerntage'];
					$experience = $this->converter->experience($lerntage, $size);
					$talent     = $this->converter->talent($skill);
					if ($talent) {
						$ability = new Ability(self::createTalent($talent), $experience);
						$unit->Knowledge()->add($ability);
					}
				}

				$sql  = "SELECT item, anzahl FROM items WHERE nummer = " . $id;
				$stmt = $this->database->query($sql);
				$stmt->execute();
				foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $itemRow) {
					$item      = $itemRow['item'];
					$amount    = (int)$itemRow['anzahl'];
					$commodity = $this->converter->commodity($item);
					if ($commodity) {
						$quantity = new Quantity(self::createCommodity($commodity), $amount);
						$unit->Inventory()->add($quantity);
					}
				}
			}
		}

		foreach ($this->owners as $cid => $uid) {
			$cid = (int)$cid;
			$uid = (int)$uid;
			if (!isset($this->constructions[$cid])) {
				Lemuria::Log()->warning('There is no construction #' . $cid . ' for owner #' . $uid . '.');
				continue;
			}
			if (!isset($this->units[$uid])) {
				Lemuria::Log()->warning('There is no owner #' . $uid . ' for construction #' . $cid . '.');
				continue;
			}
			$construction = $this->constructions[$cid];
			$unit         = $this->units[$uid];
			try {
				$construction->Inhabitants()->setOwner($unit);
			} catch (EntitySetException) {
			}
		}
		foreach ($this->captains as $vid => $uid) {
			$vid = (int)$vid;
			$uid = (int)$uid;
			if (!isset($this->vessels[$vid])) {
				Lemuria::Log()->warning('There is no vessel #' . $vid . ' for captain #' . $uid . '.');
				continue;
			}
			if (!isset($this->units[$uid])) {
				Lemuria::Log()->warning('There is no captain #' . $uid . ' of vessel #' . $vid . '.');
				continue;
			}
			$vessel = $this->vessels[$vid];
			$unit   = $this->units[$uid];
			try {
				$vessel->Passengers()->setOwner($unit);
			} catch (EntitySetException) {
			}
		}
	}

	private function saveAll(string $storage): void {
		$constructionsArray = [];
		foreach ($this->constructions as $construction /* @var Construction $construction */) {
			$constructionsArray[] = $construction->serialize();
		}
		file_put_contents($storage . '/constructions.json', json_encode($constructionsArray, JSON_PRESERVE_ZERO_FRACTION));

		$unitsArray = [];
		foreach ($this->units as $unit /* @var Unit $unit */) {
			$unitsArray[] = $unit->serialize();
		}
		file_put_contents($storage . '/units.json', json_encode($unitsArray, JSON_PRESERVE_ZERO_FRACTION));

		$partiesArray = [];
		foreach ($this->parties as $party /* @var Party $party */) {
			$partiesArray[] = $party->serialize();
		}
		file_put_contents($storage . '/parties.json', json_encode($partiesArray, JSON_PRESERVE_ZERO_FRACTION));

		$regionsArray = [];
		foreach ($this->regions as $region /* @var Region $region */) {
			$regionsArray[] = $region->serialize();
		}
		file_put_contents($storage . '/regions.json', json_encode($regionsArray, JSON_PRESERVE_ZERO_FRACTION));

		$vesselsArray = [];
		foreach ($this->vessels as $vessel /* @var Vessel $vessel */) {
			$vesselsArray[] = $vessel->serialize();
		}
		file_put_contents($storage . '/vessels.json', json_encode($vesselsArray, JSON_PRESERVE_ZERO_FRACTION));


		$mapArray = [];
		foreach ($this->map as $xRegions) {
			$mapArray[] = array_values($xRegions);
		}
		$worldArray  = [
			'origin' => ['x' => $this->minX, 'y' => $this->minY],
			'map'    => $mapArray
		];
		$world = new OctagonalMap();
		$world->unserialize($worldArray);
		file_put_contents($storage . '/world.json', json_encode($worldArray, JSON_PRESERVE_ZERO_FRACTION));
	}

	private function printMap(): void {
		foreach (array_reverse(array_keys($this->map)) as $y) {
			echo $y . ': ';
			$yRegions = $this->map[$y];
			foreach ($yRegions as $id) {
				if ($id) {
					/* @var Region $region */
					$region = $this->regions[$id];
					echo substr(getClass($region->Landscape()), 0, 1);
				} else {
					echo '.';
				}
			}
			echo PHP_EOL;
		}
	}
}
