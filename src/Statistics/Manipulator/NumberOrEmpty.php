<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Statistics\Manipulator;

use SatHub\CliTable\CliTableManipulator;

use function Lemuria\number;

class NumberOrEmpty extends CliTableManipulator
{
	public final const string FORMAT = 'format';

	public function format(int|float $value): string {
		return $value ? number($value) : '';
	}
}
