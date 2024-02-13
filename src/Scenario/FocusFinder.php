<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Scenario;

use Lemuria\Engine\Fantasya\Census;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Building\Market;
use Lemuria\Model\Fantasya\Building\Port;
use Lemuria\Model\Fantasya\Intelligence;
use Lemuria\Model\Fantasya\Landmass;
use Lemuria\Model\Fantasya\Navigable;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Party\Type;
use Lemuria\Model\Fantasya\Region;

class FocusFinder
{
	protected const array BUILDINGS = [Market::class, Port::class];

	protected Party $party;

	protected Landmass $regions;

	public function __construct() {
		$this->regions = new Landmass();
	}

	public function Landmass(): Landmass {
		if ($this->regions->isEmpty()) {
			Lemuria::Log()->debug('Focus finder is running...');
			$this->find();
		}
		return $this->regions;
	}

	public function setParty(Party $party): static {
		$this->party = $party;
		return $this;
	}

	protected function find(): void {
		$capitals = $this->findCapitals();
		foreach (Region::all() as $region) {
			if ($region->Landscape() instanceof Navigable) {
				$this->regions->add($region);
			} elseif ($capitals->has($region->Id())) {
				$this->regions->add($region);
				Lemuria::Log()->debug($region . ' is a capital.');
			} elseif ($region->Realm()?->Territory()->Central() === $region) {
				$this->regions->add($region);
				Lemuria::Log()->debug($region . ' is a realm central.');
			} elseif ($region->Roads()?->count() > 0) {
				$this->regions->add($region);
				Lemuria::Log()->debug($region . ' has roads.');
			} else {
				foreach ($region->Estate() as $construction) {
					if (in_array($construction->Building()::class, self::BUILDINGS)) {
						$this->regions->add($region);
						Lemuria::Log()->debug($region . ' has buildings of interest.');
						continue 2;
					}
				}
				foreach ($region->Residents() as $unit) {
					if ($unit->Party() === $this->party) {
						$this->regions->add($region);
						Lemuria::Log()->debug($region . ' has units of party ' . $this->party . '.');
						continue 2;
					}
				}
			}
		}
	}

	protected function findCapitals(): Landmass {
		$parties  = [];
		$capitals = new Landmass();
		foreach (Party::all() as $party) {
			if ($party->Type() !== Type::Player || $party->hasRetired()) {
				Lemuria::Log()->debug('Skipping party ' . $party . '.');
				continue;
			}

			$p              = $party->Id()->Id();
			$parties[$p]    = [];
			$regions        = [];
			$infrastructure = [];
			$census         = new Census($party);
			$atlas          = $census->getAtlas();
			foreach ($atlas as $region) {
				/** @var Region $region */
				$id           = $region->Id()->Id();
				$regions[$id] = $region;
				$intelligence = new Intelligence($region);
				$government   = $intelligence->getGovernment();
				if (!$government || $government->Inhabitants()->Owner()->Party() === $party) {
					$points = $intelligence->getInfrastructure();
					if ($points > 0) {
						$infrastructure[$id] = $points;
					}
				}
			}
			Lemuria::Log()->debug('Party ' . $party . ' has ' . count($infrastructure) . ' regions with infrastructure.');

			$i = 0;
			$n = min(count($infrastructure), 3);
			arsort($infrastructure);
			while ($i++ < $n) {
				$region        = $regions[key($infrastructure)];
				$parties[$p][] = $region;
				Lemuria::Log()->debug('Choosing ' . $region . ' with ' . current($infrastructure) . ' points for party ' . $party . '.');
				next($infrastructure);
			}
		}

		foreach ($parties as $regions) {
			foreach ($regions as $region) {
				$capitals->add($region);
			}
		}
		return $capitals;
	}
}
