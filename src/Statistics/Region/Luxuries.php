<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Statistics\Region;

use jc21\CliTable;
use Lemuria\Engine\Fantasya\Factory\GrammarTrait;
use Lemuria\Engine\Fantasya\Message\Casus;
use Lemuria\Model\Fantasya\Commodity\Luxury\AbstractLuxury;
use Lemuria\Model\Fantasya\Landmass;
use Lemuria\Model\Fantasya\Luxury;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Tools\Lemuria\Statistics\Manipulator\NumberOrEmpty;
use Lemuria\Tools\Lemuria\Statistics\Manipulator\Translator;

class Luxuries
{
	use GrammarTrait;

	private const LANDSCAPE = 'landscape';

	private const TRANSLATION = 'Landschaft';

	private CliTable $table;

	private array $row = [self::LANDSCAPE => null];

	public function __construct() {
		$this->table = new CliTable();
		$this->table->addField(self::TRANSLATION, self::LANDSCAPE, new Translator(Translator::TRANSLATE));
		foreach (AbstractLuxury::all() as $luxury) {
			$key             = (string)$luxury;
			$name            = $this->translateSingleton($luxury, casus: Casus::Nominative);
			$this->row[$key] = 0;
			$this->table->addField($name, $key, new NumberOrEmpty(NumberOrEmpty::FORMAT));
		}
	}

	public function collect(?Landmass $landmass = null): static {
		$data       = [];
		$landscapes = $this->collectLandscapes($landmass);
		foreach ($landscapes as $landscape => $luxuries) {
			$row                  = $this->row;
			$row[self::LANDSCAPE] = $landscape;
			ksort($luxuries);
			foreach ($luxuries as $luxury => $count) {
				$row[$luxury] = $count;
			}
			$data[] = $row;
		}
		$this->table->injectData($data);
		return $this;
	}

	public function getTable(): CliTable {
		return $this->table;
	}

	private function collectLandscapes(?Landmass $landmass = null): array {
		if (!$landmass) {
			$landmass = Region::all();
		}

		$landscapes = [];
		foreach ($landmass as $region) {
			$landscape = $region->Landscape()->__toString();
			$offer     = $region->Luxuries()?->Offer()->Commodity();
			if ($offer instanceof Luxury) {
				$luxury = $offer->__toString();
				if (isset($landscapes[$landscape][$luxury])) {
					$landscapes[$landscape][$luxury]++;
				} else {
					$landscapes[$landscape][$luxury] = 1;
				}
			}
		}
		ksort($landscapes);
		return $landscapes;
	}
}
