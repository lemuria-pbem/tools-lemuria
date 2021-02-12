<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

use JetBrains\PhpStorm\Pure;

class MapRenderer implements \Stringable
{
	protected const TABS = 2;

	protected const ROW_CLASS = 'y';

	protected const PLACEHOLDER = '$region';

	protected const TYPE_CLASS = 'landscape-' . self::PLACEHOLDER;

	public static int $tabs = self::TABS;

	private int $width;

	private int $height;

	#[Pure] public function __construct(private Map $map) {
		$this->width  = $map->Width();
		$this->height = $map->Height();
	}

	public function __toString(): string {
		ob_start();

		$m = $this->height + 1;
		self::printLine('<div class="' . self::ROW_CLASS . '" style="margin-left: ' . $m . 'em;">');
		self::printColumns();
		self::printLine('</div>', 1);

		for ($y = $this->height - 1; $y >= 0; $y--) {
			$this->map->setY($y);
			self::printLine('<div class="' . self::ROW_CLASS . '" style="margin-left: ' . $y . 'em;">', 1);
			self::printTabbed('<div>' . $y . '</div>', 2);
			for ($x = 0; $x < $this->width; $x++) {
				$this->map->setX($x);
				$type   = $this->map->Type();
				$temp   = $this->map->Temperature();
				$precip = $this->map->Precipitation();
				$class  = $this->getRegion();
				$title  = $type . ': (' . $x . '/' . $y . ') ' . $this->map->Altitude() . 'm / ' . $temp . '°C / ' . $precip;
				echo '<div class="' . $class . '" title="' . $title . '">&nbsp;</div>';
			}
			self::printLine('<div>' . $y . '</div>');
			self::printLine('</div>', 1);
		}

		self::printLine('<div class="' . self::ROW_CLASS . '" style="margin-left: 2em;">', 1);
		self::printColumns();
		self::printLine('</div>');

		return ob_get_clean();
	}

	protected function printLine(string $html, ?int $indent = null): void {
		$this->printTabbed($html, $indent);
		echo PHP_EOL;
	}

	protected function printColumns(): void {
		self::printTabbed(indent: 2);
		for ($x = 0; $x < $this->width; $x++) {
			echo '<div>' . $x . '</div>';
		}
		echo PHP_EOL;
	}

	protected function getAltitude(int $x, int $y): int {
		return $this->map[$y][$x];
	}

	protected function getRegion(): string {
		return str_replace(self::PLACEHOLDER, (string)$this->map->Type(), self::TYPE_CLASS);
	}

	protected static function printTabbed(string $html = '', ?int $indent = null): void {
		if (is_int($indent)) {
			echo str_repeat("\t", self::$tabs + $indent);
		}
		echo $html;
	}
}
