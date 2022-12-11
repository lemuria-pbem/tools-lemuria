<?php
declare (strict_types = 1);
namespace Lemuria\Tools\Lemuria\Fantasya;

use Lemuria\Model\World\Direction;
use function Lemuria\getClass;
use Lemuria\Model\Fantasya\Ability;
use Lemuria\Model\Fantasya\Building\AbstractCastle;
use Lemuria\Model\Fantasya\Building\Cabin;
use Lemuria\Model\Fantasya\Building\Sawmill;
use Lemuria\Model\Fantasya\Combat\BattleRow;
use Lemuria\Model\Fantasya\Commodity\Camel;
use Lemuria\Model\Fantasya\Commodity\Carriage;
use Lemuria\Model\Fantasya\Commodity\Elephant;
use Lemuria\Model\Fantasya\Commodity\Gold;
use Lemuria\Model\Fantasya\Commodity\Griffin;
use Lemuria\Model\Fantasya\Commodity\Griffinegg;
use Lemuria\Model\Fantasya\Commodity\Horse;
use Lemuria\Model\Fantasya\Commodity\Iron;
use Lemuria\Model\Fantasya\Commodity\Luxury\Balsam;
use Lemuria\Model\Fantasya\Commodity\Luxury\Fur;
use Lemuria\Model\Fantasya\Commodity\Luxury\Gem;
use Lemuria\Model\Fantasya\Commodity\Luxury\Myrrh;
use Lemuria\Model\Fantasya\Commodity\Luxury\Oil;
use Lemuria\Model\Fantasya\Commodity\Luxury\Olibanum;
use Lemuria\Model\Fantasya\Commodity\Luxury\Silk;
use Lemuria\Model\Fantasya\Commodity\Luxury\Spice;
use Lemuria\Model\Fantasya\Commodity\Pegasus;
use Lemuria\Model\Fantasya\Commodity\Protection\Armor;
use Lemuria\Model\Fantasya\Commodity\Protection\Ironshield;
use Lemuria\Model\Fantasya\Commodity\Protection\Mail;
use Lemuria\Model\Fantasya\Commodity\Protection\Woodshield;
use Lemuria\Model\Fantasya\Commodity\Silver;
use Lemuria\Model\Fantasya\Commodity\Stone;
use Lemuria\Model\Fantasya\Commodity\Weapon\Battleaxe;
use Lemuria\Model\Fantasya\Commodity\Weapon\Bow;
use Lemuria\Model\Fantasya\Commodity\Weapon\Catapult;
use Lemuria\Model\Fantasya\Commodity\Weapon\Crossbow;
use Lemuria\Model\Fantasya\Commodity\Weapon\Spear;
use Lemuria\Model\Fantasya\Commodity\Weapon\Sword;
use Lemuria\Model\Fantasya\Commodity\Weapon\Warhammer;
use Lemuria\Model\Fantasya\Commodity\Wood;
use Lemuria\Model\Fantasya\Landscape\Desert;
use Lemuria\Model\Fantasya\Landscape\Forest;
use Lemuria\Model\Fantasya\Landscape\Highland;
use Lemuria\Model\Fantasya\Landscape\Glacier;
use Lemuria\Model\Fantasya\Landscape\Mountain;
use Lemuria\Model\Fantasya\Landscape\Ocean;
use Lemuria\Model\Fantasya\Landscape\Plain;
use Lemuria\Model\Fantasya\Landscape\Swamp;
use Lemuria\Model\Fantasya\Luxury;
use Lemuria\Model\Fantasya\Race\Aquan;
use Lemuria\Model\Fantasya\Race\Dwarf;
use Lemuria\Model\Fantasya\Race\Elf;
use Lemuria\Model\Fantasya\Race\Halfling;
use Lemuria\Model\Fantasya\Race\Human;
use Lemuria\Model\Fantasya\Race\Orc;
use Lemuria\Model\Fantasya\Race\Troll;
use Lemuria\Model\Fantasya\Relation;
use Lemuria\Model\Fantasya\Ship\Boat;
use Lemuria\Model\Fantasya\Ship\Caravel;
use Lemuria\Model\Fantasya\Ship\Dragonship;
use Lemuria\Model\Fantasya\Ship\Galleon;
use Lemuria\Model\Fantasya\Ship\Longboat;
use Lemuria\Model\Fantasya\Ship\Trireme;
use Lemuria\Model\Fantasya\Talent\Archery;
use Lemuria\Model\Fantasya\Talent\Armory;
use Lemuria\Model\Fantasya\Talent\Bladefighting;
use Lemuria\Model\Fantasya\Talent\Bowmaking;
use Lemuria\Model\Fantasya\Talent\Camouflage;
use Lemuria\Model\Fantasya\Talent\Carriagemaking;
use Lemuria\Model\Fantasya\Talent\Catapulting;
use Lemuria\Model\Fantasya\Talent\Constructing;
use Lemuria\Model\Fantasya\Talent\Crossbowing;
use Lemuria\Model\Fantasya\Talent\Entertaining;
use Lemuria\Model\Fantasya\Talent\Espionage;
use Lemuria\Model\Fantasya\Talent\Horsetaming;
use Lemuria\Model\Fantasya\Talent\Magic;
use Lemuria\Model\Fantasya\Talent\Mining;
use Lemuria\Model\Fantasya\Talent\Navigation;
use Lemuria\Model\Fantasya\Talent\Perception;
use Lemuria\Model\Fantasya\Talent\Quarrying;
use Lemuria\Model\Fantasya\Talent\Riding;
use Lemuria\Model\Fantasya\Talent\Roadmaking;
use Lemuria\Model\Fantasya\Talent\Shipbuilding;
use Lemuria\Model\Fantasya\Talent\Spearfighting;
use Lemuria\Model\Fantasya\Talent\Stamina;
use Lemuria\Model\Fantasya\Talent\Tactics;
use Lemuria\Model\Fantasya\Talent\Taxcollecting;
use Lemuria\Model\Fantasya\Talent\Trading;
use Lemuria\Model\Fantasya\Talent\Weaponry;
use Lemuria\Model\Fantasya\Talent\Woodchopping;

final class Converter
{
	public function landscape(string $typ): string {
		return match ($typ) {
			'Ozean'     => Ocean::class,
			'Ebene'     => Plain::class,
			'Wald'      => Forest::class,
			'Berge'     => Mountain::class,
			'Sumpf'     => Swamp::class,
			'Hochland'  => Highland::class,
			'Gletscher' => Glacier::class,
			'Wueste'    => Desert::class,
			default     => throw new \InvalidArgumentException('Invalid type: ' . $typ),
		};
	}

	public function resource(string $resource): string {
		return match ($resource) {
			'Holz'    => Wood::class,
			'Eisen'   => Iron::class,
			'Stein'   => Stone::class,
			'Schwert' => Sword::class,
			'Speer'   => Spear::class,
			'Gold', 'Pferd', 'Kamel', 'Elefant', 'Pegasus', 'Greif', 'Alpaka', 'Zotte' => '',
			default   => throw new \InvalidArgumentException('Invalid resource: ' . $resource),
		};
	}

	public function building(string $type, int $size): string {
		switch ($type) {
			case 'Burg' :
				$castle = AbstractCastle::forSize($size);
				return getClass($castle);
			case 'Holzfaellerhuette' :
				return Cabin::class;
			case 'Saegewerk' :
				return Sawmill::class;
			default :
				return '';
		}
	}

	public function race(string $race): string {
		return match ($race) {
			'Aquaner'  => Aquan::class,
			'Elf'      => Elf::class,
			'Halbling' => Halfling::class,
			'Mensch'   => Human::class,
			'Ork'      => Orc::class,
			'Troll'    => Troll::class,
			'Zwerg'    => Dwarf::class,
			'Dragonfly', 'Echse', 'Goblin', 'Greif', 'Hoellenhund', 'Kobold', 'Krake', 'Puschkin' => '',
			default    => throw new \InvalidArgumentException('Invalid race: ' . $race),
		};
	}

	public function ship(string $ship): string {
		return match ($ship) {
			'Boot'          => Boat::class,
			'Drachenschiff' => Dragonship::class,
			'Galeone'       => Galleon::class,
			'Karavelle'     => Caravel::class,
			'Langboot'      => Longboat::class,
			'Tireme'        => Trireme::class,
			default         => throw new \InvalidArgumentException('Invalid ship: ' . $ship),
		};
	}

	public function anchor(string $anchor): Direction {
		return Direction::from(str_replace('O', 'E', $anchor));
	}

	public function talent(string $talent): string {
		return match ($talent) {
			'Armbrustschiessen' => Crossbowing::class,
			'Ausdauer'          => Stamina::class,
			'Bergbau'           => Mining::class,
			'Bogenbau'          => Bowmaking::class,
			'Bogenschiessen'    => Archery::class,
			'Burgenbau'         => Constructing::class,
			'Handel'            => Trading::class,
			'Hiebwaffen'        => Bladefighting::class,
			'Holzfaellen'       => Woodchopping::class,
			'Katapultbedienung' => Catapulting::class,
			'Magie'             => Magic::class,
			'Pferdedressur'     => Horsetaming::class,
			'Reiten'            => Riding::class,
			'Ruestungsbau'      => Armory::class,
			'Schiffbau'         => Shipbuilding::class,
			'Segeln'            => Navigation::class,
			'Speerkampf'        => Spearfighting::class,
			'Spionage'          => Espionage::class,
			'Steinbau'          => Quarrying::class,
			'Steuereintreiben'  => Taxcollecting::class,
			'Strassenbau'       => Roadmaking::class,
			'Taktik'            => Tactics::class,
			'Tarnung'           => Camouflage::class,
			'Unterhaltung'      => Entertaining::class,
			'Waffenbau'         => Weaponry::class,
			'Wagenbau'          => Carriagemaking::class,
			'Wahrnehmung'       => Perception::class,
			'Alchemie', 'Drachenreiten', 'Kraeuterkunde', 'Monsterkampf', 'Religion' => '',
			default             => throw new \InvalidArgumentException('Invalid skill: ' . $talent),
		};
	}

	public function commodity(string $commodity): string {
		return match ($commodity) {
			'Armbrust'                   => Crossbow::class,
			'Balsam'                     => Balsam::class,
			'Bogen'                      => Bow::class,
			'Eisen'                      => Iron::class,
			'Eisenschild'                => Ironshield::class,
			'Elefant', 'Kriegselefant'   => Elephant::class,
			'Gewuerz'                    => Spice::class,
			'Gold'                       => Gold::class,
			'Greif'                      => Griffin::class,
			'Greifenei'                  => Griffinegg::class,
			'Holz'                       => Wood::class,
			'Holzschild'                 => Woodshield::class,
			'Juwel'                      => Gem::class,
			'Kamel', 'Alpaka'            => Camel::class,
			'Katapult'                   => Catapult::class,
			'Kettenhemd'                 => Mail::class,
			'Kriegshammer'               => Warhammer::class,
			'Myhrre'                     => Myrrh::class,
			'Oel'                        => Oil::class,
			'Pegasus'                    => Pegasus::class,
			'Pelz'                       => Fur::class,
			'Pferd', 'Zotte'             => Horse::class,
			'Plattenpanzer'              => Armor::class,
			'Schwert'                    => Sword::class,
			'Seide'                      => Silk::class,
			'Silber'                     => Silver::class,
			'Speer'                      => Spear::class,
			'Stein'                      => Stone::class,
			'Streitaxt'                  => Battleaxe::class,
			'Wagen'                      => Carriage::class,
			'Weihrauch'                  => Olibanum::class,
			'Einhorn', 'Elefantenpanzer' => '',
			default => throw new \InvalidArgumentException('Invalid commodity: ' . $commodity),
		};
	}

	public function battleRow(string $kampfposition): BattleRow {
		return match ($kampfposition) {
			'Vorne'     => BattleRow::Front,
			'Hinten'    => BattleRow::Back,
			'Nicht'     => BattleRow::Bystander,
			'Aggressiv' => BattleRow::Aggressive,
			'Fliehe'    => BattleRow::Refugee,
			default     => throw new \InvalidArgumentException('Invalid battle row: ' . $kampfposition),
		};
	}

	public function experience(int $lerntage, int $size): int {
		$level = (int)sqrt($lerntage / ($size * 15));
		return Ability::getExperience($level);
	}

	public function agreement(string $option): int {
		return match (strtolower($option)) {
			'gib'         => Relation::GIVE,
			'handel'      => Relation::TRADE,
			'kaempfe'     => Relation::COMBAT,
			'kontaktiere' => Relation::TELL | Relation::TRADE | Relation::RESOURCES | Relation::ENTER,
			'resourcen'   => Relation::RESOURCES,
			'steuern', 'treiben', 'unterhalte' => Relation::EARN,
			default       => throw new \InvalidArgumentException('Invalid agreement: ' . $option),
		};
	}

	public function luxury(string $luxus): string {
		return match ($luxus) {
			'Balsam'    => Balsam::class,
			'Gewuerz'   => Spice::class,
			'Juwel'     => Gem::class,
			'Myhrre'    => Myrrh::class,
			'Oel'       => Oil::class,
			'Pelz'      => Fur::class,
			'Seide'     => Silk::class,
			'Weihrauch' => Olibanum::class,
			default     => throw new \InvalidArgumentException('Invalid luxury: ' . $luxus),
		};
	}

	public function demand(Luxury $luxury, int $nachfrage): int {
		if ($nachfrage > 100000) {
			throw new \InvalidArgumentException('Invalid demand: ' . $nachfrage);
		}
		$price  = $luxury->Value();
		$demand = (int)round(abs($nachfrage) / 1000);
		return $demand * $price;
	}
}
