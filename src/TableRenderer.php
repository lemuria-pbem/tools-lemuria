<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

class TableRenderer extends MapRenderer
{
	public function __toString(): string {
		ob_start();

		self::printLine('<table class="table">');
		self::printLine('<thead>', 1);
		self::printLine('<tr>', 2);
		self::printLine('<th>x</th>', 3);
		self::printLine('<th>y</th>', 3);
		foreach (array_keys($this->map->to(0, 0)->Resource()) as $z) {
			self::printLine('<th>' . $z . '</th>', 3);
		}
		self::printLine('</tr>', 2);
		self::printLine('</thead>', 1);
		self::printLine('<tbody>', 1);
		$this->printRows();
		self::printLine('</tbody>', 1);
		self::printLine('</table>');

		return ob_get_clean();
	}

	private function printRows(): void {
		for ($y = 0; $y < $this->height; $y++) {
			$this->map->setY($y);
			for ($x = 0; $x < $this->width; $x++) {
				$this->map->setX($x);
				$values = $this->map->Resource();
				if (array_sum($values) <= 0) {
					continue;
				}

				self::printLine('<tr>', 2);
				self::printLine('<th>' . $x . '</th>', 3);
				self::printLine('<th>' . $y . '</th>', 3);
				foreach ($values as $amount) {
					self::printLine('<td>' . $amount . '</td>', 3);
				}
				self::printLine('</tr>', 2);
			}
		}
	}
}
