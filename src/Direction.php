<?php
declare(strict_types = 1);
namespace Lemuria\Tools\Lemuria;

final class Direction
{
	public const array NONE = [];

	/**
	 * @type array<int>
	 */
	public const array NE = [0, 1];

	/**
	 * @type array<int>
	 */
	public const array E = [1, 0];

	/**
	 * @type array<int>
	 */
	public const array SE = [1, -1];

	/**
	 * @type array<int>
	 */
	public const array SW = [0, -1];

	/**
	 * @type array<int>
	 */
	public const array W = [-1, 0];

	/**
	 * @type array<int>
	 */
	public const array NW = [-1, 1];
}
