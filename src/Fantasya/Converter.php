<?php
declare (strict_types = 1);
namespace Lemuria\Tools\Lemuria\Fantasya;

use JetBrains\PhpStorm\Pure;

use function Lemuria\getClass;
use Lemuria\Model\Fantasya\Ability;
use Lemuria\Model\Fantasya\Building\AbstractCastle;
use Lemuria\Model\Fantasya\Building\Cabin;
use Lemuria\Model\Fantasya\Building\Sawmill;
use Lemuria\Model\Fantasya\Combat;
use Lemuria\Model\Fantasya\Commodity\Armor;
use Lemuria\Model\Fantasya\Commodity\Camel;
use Lemuria\Model\Fantasya\Commodity\Carriage;
use Lemuria\Model\Fantasya\Commodity\Elephant;
use Lemuria\Model\Fantasya\Commodity\Gold;
use Lemuria\Model\Fantasya\Commodity\Griffin;
use Lemuria\Model\Fantasya\Commodity\Griffinegg;
use Lemuria\Model\Fantasya\Commodity\Horse;
use Lemuria\Model\Fantasya\Commodity\Iron;
use Lemuria\Model\Fantasya\Commodity\Ironshield;
use Lemuria\Model\Fantasya\Commodity\Luxury\Balsam;
use Lemuria\Model\Fantasya\Commodity\Luxury\Fur;
use Lemuria\Model\Fantasya\Commodity\Luxury\Gem;
use Lemuria\Model\Fantasya\Commodity\Luxury\Myrrh;
use Lemuria\Model\Fantasya\Commodity\Luxury\Oil;
use Lemuria\Model\Fantasya\Commodity\Luxury\Olibanum;
use Lemuria\Model\Fantasya\Commodity\Luxury\Silk;
use Lemuria\Model\Fantasya\Commodity\Luxury\Spice;
use Lemuria\Model\Fantasya\Commodity\Mail;
use Lemuria\Model\Fantasya\Commodity\Pegasus;
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
use Lemuria\Model\Fantasya\Commodity\Woodshield;
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
				return Wood::class;
			case 'Eisen' :
				return Iron::class;
			case 'Stein' :
				return Stone::class;
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
