<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Statistics\Manipulator;

use jc21\CliTableManipulator;

use Lemuria\Engine\Fantasya\Factory\GrammarTrait;
use Lemuria\Engine\Fantasya\Message\Casus;

class Translator extends CliTableManipulator
{
	use GrammarTrait;

	public final const TRANSLATE = 'translate';

	public function translate(string $value): string {
		return $this->translateSingleton($value, casus: Casus::Nominative);
	}
}
