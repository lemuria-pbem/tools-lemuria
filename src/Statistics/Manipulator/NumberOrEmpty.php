<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria\Statistics\Manipulator;

use jc21\CliTableManipulator;

use function Lemuria\number;

class NumberOrEmpty extends CliTableManipulator
{
	public final const FORMAT = 'format';

	public function format(int|float $value): string {
		return $value ? number($value) : '';
	}
}
