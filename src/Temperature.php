<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

final class Temperature
{
	private float $equator;

	private float $hTemp;

	public function __construct(MapConfig $config) {
		$this->equator = $config->height / 2;
		$this->hTemp   = $config->hTemp;
	}

	public function forY(int $y): float {
		return $y < $this->equator ?
			($y / $this->equator) * 29.0 - 2.0 :
			27.0 - ($y - $this->equator) / $this->equator * 29.0;
	}

	public function forAltitude(int $y, int $altitude): float {
		$temperature = $this->forY($y);
		if ($altitude > 0) {
			$temperature -= $altitude * $this->hTemp / 100.0;
		}
		return $temperature;
	}

	public function toMoist(float $temperature): float {
		return (0.02616 * $temperature * $temperature + 0.2276 * $temperature + 4.5227);
	}
}
