<?php
declare (strict_types = 1);
namespace Lemuria\Tools\Lemuria\Fantasya;

use JetBrains\PhpStorm\Pure;

use function Lemuria\getClass;
use Lemuria\Model\Lemuria\Ability;
use Lemuria\Model\Lemuria\Building\AbstractCastle;
use Lemuria\Model\Lemuria\Building\Cabin;
use Lemuria\Model\Lemuria\Building\Sawmill;
use Lemuria\Model\Lemuria\Combat;
use Lemuria\Model\Lemuria\Commodity\Armor;
use Lemuria\Model\Lemuria\Commodity\Camel;
use Lemuria\Model\Lemuria\Commodity\Carriage;
use Lemuria\Model\Lemuria\Commodity\Elephant;
use Lemuria\Model\Lemuria\Commodity\Gold;
use Lemuria\Model\Lemuria\Commodity\Granite;
use Lemuria\Model\Lemuria\Commodity\Griffin;
use Lemuria\Model\Lemuria\Commodity\Griffinegg;
use Lemuria\Model\Lemuria\Commodity\Horse;
use Lemuria\Model\Lemuria\Commodity\Iron;
use Lemuria\Model\Lemuria\Commodity\Ironshield;
use Lemuria\Model\Lemuria\Commodity\Luxury\Balsam;
use Lemuria\Model\Lemuria\Commodity\Luxury\Fur;
use Lemuria\Model\Lemuria\Commodity\Luxury\Gem;
use Lemuria\Model\Lemuria\Commodity\Luxury\Myrrh;
use Lemuria\Model\Lemuria\Commodity\Luxury\Oil;
use Lemuria\Model\Lemuria\Commodity\Luxury\Olibanum;
use Lemuria\Model\Lemuria\Commodity\Luxury\Silk;
use Lemuria\Model\Lemuria\Commodity\Luxury\Spice;
use Lemuria\Model\Lemuria\Commodity\Mail;
use Lemuria\Model\Lemuria\Commodity\Ore;
use Lemuria\Model\Lemuria\Commodity\Pegasus;
use Lemuria\Model\Lemuria\Commodity\Silver;
use Lemuria\Model\Lemuria\Commodity\Stone;
use Lemuria\Model\Lemuria\Commodity\Tree;
use Lemuria\Model\Lemuria\Commodity\Weapon\Battleaxe;
use Lemuria\Model\Lemuria\Commodity\Weapon\Bow;
use Lemuria\Model\Lemuria\Commodity\Weapon\Catapult;
use Lemuria\Model\Lemuria\Commodity\Weapon\Crossbow;
use Lemuria\Model\Lemuria\Commodity\Weapon\Spear;
use Lemuria\Model\Lemuria\Commodity\Weapon\Sword;
use Lemuria\Model\Lemuria\Commodity\Weapon\Warhammer;
use Lemuria\Model\Lemuria\Commodity\Wood;
use Lemuria\Model\Lemuria\Commodity\Woodshield;
use Lemuria\Model\Lemuria\Landscape\Desert;
use Lemuria\Model\Lemuria\Landscape\Forest;
use Lemuria\Model\Lemuria\Landscape\Highland;
use Lemuria\Model\Lemuria\Landscape\Glacier;
use Lemuria\Model\Lemuria\Landscape\Mountain;
use Lemuria\Model\Lemuria\Landscape\Ocean;
use Lemuria\Model\Lemuria\Landscape\Plain;
use Lemuria\Model\Lemuria\Landscape\Swamp;
use Lemuria\Model\Lemuria\Luxury;
use Lemuria\Model\Lemuria\Race\Aquan;
use Lemuria\Model\Lemuria\Race\Dwarf;
use Lemuria\Model\Lemuria\Race\Elf;
use Lemuria\Model\Lemuria\Race\Halfling;
use Lemuria\Model\Lemuria\Race\Human;
use Lemuria\Model\Lemuria\Race\Orc;
use Lemuria\Model\Lemuria\Race\Troll;
use Lemuria\Model\Lemuria\Relation;
use Lemuria\Model\Lemuria\Ship\Boat;
use Lemuria\Model\Lemuria\Ship\Caravel;
use Lemuria\Model\Lemuria\Ship\Dragonship;
use Lemuria\Model\Lemuria\Ship\Galleon;
use Lemuria\Model\Lemuria\Ship\Longboat;
use Lemuria\Model\Lemuria\Ship\Trireme;
use Lemuria\Model\Lemuria\Talent\Archery;
use Lemuria\Model\Lemuria\Talent\Armory;
use Lemuria\Model\Lemuria\Talent\Bladefighting;
use Lemuria\Model\Lemuria\Talent\Bowmaking;
use Lemuria\Model\Lemuria\Talent\Camouflage;
use Lemuria\Model\Lemuria\Talent\Carriagemaking;
use Lemuria\Model\Lemuria\Talent\Catapulting;
use Lemuria\Model\Lemuria\Talent\Constructing;
use Lemuria\Model\Lemuria\Talent\Crossbowing;
use Lemuria\Model\Lemuria\Talent\Entertaining;
use Lemuria\Model\Lemuria\Talent\Espionage;
use Lemuria\Model\Lemuria\Talent\Horsetaming;
use Lemuria\Model\Lemuria\Talent\Magic;
use Lemuria\Model\Lemuria\Talent\Mining;
use Lemuria\Model\Lemuria\Talent\Navigation;
use Lemuria\Model\Lemuria\Talent\Perception;
use Lemuria\Model\Lemuria\Talent\Quarrying;
use Lemuria\Model\Lemuria\Talent\Riding;
use Lemuria\Model\Lemuria\Talent\Roadmaking;
use Lemuria\Model\Lemuria\Talent\Shipbuilding;
use Lemuria\Model\Lemuria\Talent\Spearfighting;
use Lemuria\Model\Lemuria\Talent\Stamina;
use Lemuria\Model\Lemuria\Talent\Tactics;
use Lemuria\Model\Lemuria\Talent\Taxcollecting;
use Lemuria\Model\Lemuria\Talent\Trading;
use Lemuria\Model\Lemuria\Talent\Weaponry;
use Lemuria\Model\Lemuria\Talent\Woodchopping;

final class Converter
{
	public function landscape(string $typ): string {
		switch ($typ) {
			case 'Ozean' :
				return Ocean::class;
			case 'Ebene' :
				return Plain::class;
			case 'Wald' :
				return Forest::class;
			case 'Berge' :
				return Mountain::class;
			case 'Sumpf' :
				return Swamp::class;
			case 'Hochland' :
				return Highland::class;
			case 'Gletscher' :
				return Glacier::class;
			case 'Wueste' :
				return Desert::class;
			default :
				throw new \InvalidArgumentException('Invalid type: ' . $typ);
		}
	}

	public function resource(string $resource): string {
		switch ($resource) {
			case 'Holz' :
				return Tree::class;
			case 'Eisen' :
				return Ore::class;
			case 'Stein' :
				return Granite::class;
			case 'Schwert' :
				return Sword::class;
			case 'Speer' :
				return Spear::class;
			case 'Gold' :
			case 'Pferd' :
			case 'Kamel' :
			case 'Elefant' :
			case 'Pegasus' :
			case 'Greif' :
			case 'Alpaka' :
			case 'Zotte' :
				return '';
			default :
				throw new \InvalidArgumentException('Invalid resource: ' . $resource);
		}
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
		switch ($race) {
			case 'Aquaner' :
				return Aquan::class;
			case 'Elf' :
				return Elf::class;
			case 'Halbling' :
				return Halfling::class;
			case 'Mensch' :
				return Human::class;
			case 'Ork' :
				return Orc::class;
			case 'Troll' :
				return Troll::class;
			case 'Zwerg' :
				return Dwarf::class;
			case 'Dragonfly' :
			case 'Echse' :
			case 'Goblin' :
			case 'Greif' :
			case 'Hoellenhund' :
			case 'Kobold' :
			case 'Krake' :
			case 'Puschkin' :
				return '';
			default :
				throw new \InvalidArgumentException('Invalid race: ' . $race);
		}
	}

	public function ship(string $ship): string {
		switch ($ship) {
			case 'Boot' :
				return Boat::class;
			case 'Drachenschiff' :
				return Dragonship::class;
			case 'Galeone' :
				return Galleon::class;
			case 'Karavelle' :
				return Caravel::class;
			case 'Langboot' :
				return Longboat::class;
			case 'Tireme' :
				return Trireme::class;
			default :
				throw new \InvalidArgumentException('Invalid ship: ' . $ship);
		}
	}

	#[Pure] public function anchor(string $anchor): string {
		return str_replace('O', 'E', $anchor);
	}

	public function talent(string $talent): string {
		switch ($talent) {
			case 'Armbrustschiessen' :
				return Crossbowing::class;
			case 'Ausdauer' :
				return Stamina::class;
			case 'Bergbau' :
				return Mining::class;
			case 'Bogenbau' :
				return Bowmaking::class;
			case 'Bogenschiessen' :
				return Archery::class;
			case 'Burgenbau' :
				return Constructing::class;
			case 'Handel' :
				return Trading::class;
			case 'Hiebwaffen' :
				return Bladefighting::class;
			case 'Holzfaellen' :
				return Woodchopping::class;
			case 'Katapultbedienung' :
				return Catapulting::class;
			case 'Magie' :
				return Magic::class;
			case 'Pferdedressur' :
				return Horsetaming::class;
			case 'Reiten' :
				return Riding::class;
			case 'Ruestungsbau' :
				return Armory::class;
			case 'Schiffbau' :
				return Shipbuilding::class;
			case 'Segeln' :
				return Navigation::class;
			case 'Speerkampf' :
				return Spearfighting::class;
			case 'Spionage' :
				return Espionage::class;
			case 'Steinbau' :
				return Quarrying::class;
			case 'Steuereintreiben' :
				return Taxcollecting::class;
			case 'Strassenbau' :
				return Roadmaking::class;
			case 'Taktik' :
				return Tactics::class;
			case 'Tarnung' :
				return Camouflage::class;
			case 'Unterhaltung' :
				return Entertaining::class;
			case 'Waffenbau' :
				return Weaponry::class;
			case 'Wagenbau' :
				return Carriagemaking::class;
			case 'Wahrnehmung' :
				return Perception::class;
			case 'Alchemie' :
			case 'Drachenreiten' :
			case 'Kraeuterkunde' :
			case 'Monsterkampf' :
			case 'Religion' :
				return '';
			default :
				throw new \InvalidArgumentException('Invalid skill: ' . $talent);
		}
	}

	public function commodity(string $commodity): string {
		switch ($commodity) {
			case 'Armbrust' :
				return Crossbow::class;
			case 'Balsam' :
				return Balsam::class;
			case 'Bogen' :
				return Bow::class;
			case 'Eisen' :
				return Iron::class;
			case 'Eisenschild' :
				return Ironshield::class;
			case 'Elefant' :
			case 'Kriegselefant' :
				return Elephant::class;
			case 'Gewuerz' :
				return Spice::class;
			case 'Gold' :
				return Gold::class;
			case 'Greif' :
				return Griffin::class;
			case 'Greifenei' :
				return Griffinegg::class;
			case 'Holz' :
				return Wood::class;
			case 'Holzschild' :
				return Woodshield::class;
			case 'Juwel' :
				return Gem::class;
			case 'Kamel' :
			case'Alpaka' :
				return Camel::class;
			case 'Katapult' :
				return Catapult::class;
			case 'Kettenhemd' :
				return Mail::class;
			case 'Kriegshammer' :
				return Warhammer::class;
			case 'Myhrre' :
				return Myrrh::class;
			case 'Oel' :
				return Oil::class;
			case 'Pegasus' :
				return Pegasus::class;
			case 'Pelz' :
				return Fur::class;
			case 'Pferd' :
			case 'Zotte' :
				return Horse::class;
			case 'Plattenpanzer' :
				return Armor::class;
			case 'Schwert' :
				return Sword::class;
			case 'Seide' :
				return Silk::class;
			case 'Silber' :
				return Silver::class;
			case 'Speer' :
				return Spear::class;
			case 'Stein' :
				return Stone::class;
			case 'Streitaxt' :
				return Battleaxe::class;
			case 'Wagen' :
				return Carriage::class;
			case 'Weihrauch' :
				return Olibanum::class;
			case 'Einhorn' :
			case 'Elefantenpanzer' :
				return '';
			default :
				throw new \InvalidArgumentException('Invalid commodity: ' . $commodity);
		}
	}

	public function battleRow(string $kampfposition): int {
		switch ($kampfposition) {
			case 'Vorne' :
				return Combat::FRONT;
			case 'Hinten' :
				return Combat::BACK;
			case 'Nicht' :
				return Combat::BYSTANDER;
			case 'Aggressiv' :
				return Combat::AGGRESSIVE;
			case 'Fliehe' :
				return Combat::REFUGEE;
			default :
				throw new \InvalidArgumentException('Invalid battle row: ' . $kampfposition);
		}
	}

	public function experience(int $lerntage, int $size): int {
		$level      = (int)sqrt($lerntage / ($size * 15));
		$experience = Ability::getExperience($level);
		return $experience;
	}

	public function agreement(string $option): int {
		switch (strtolower($option)) {
			case 'gib' :
				return Relation::GIVE;
			case 'handel' :
				return Relation::TRADE;
			case 'kaempfe' :
				return Relation::COMBAT;
			case 'kontaktiere' :
				return Relation::TELL | Relation::TRADE | Relation::RESOURCES | Relation::ENTER;
			case 'resourcen' :
				return Relation::RESOURCES;
			case 'steuern' :
			case 'treiben' :
			case 'unterhalte' :
				return Relation::EARN;
			default :
				throw new \InvalidArgumentException('Invalid agreement: ' . $option);
		}
	}

	public function luxury(string $luxus): string {
		switch ($luxus) {
			case 'Balsam' :
				return Balsam::class;
			case 'Gewuerz' :
				return Spice::class;
			case 'Juwel' :
				return Gem::class;
			case 'Myhrre' :
				return Myrrh::class;
			case 'Oel' :
				return Oil::class;
			case 'Pelz' :
				return Fur::class;
			case 'Seide' :
				return Silk::class;
			case 'Weihrauch' :
				return Olibanum::class;
			default :
				throw new \InvalidArgumentException('Invalid luxury: ' . $luxus);
		}
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
