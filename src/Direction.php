<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

final class Direction
{
	public const NONE = [];

	public const NE = [0, 1];

	public const E = [1, 0];

	public const SE = [1, -1];

	public const SW = [0, -1];

	public const W = [-1, 0];

	public const NW = [-1, 1];
}
