<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tester\Runner;


/**
 * Test represents one result.
 */
class Test
{
	public const
		PREPARED = 0,
		FAILED = 1,
		PASSED = 2,
		SKIPPED = 3;

	/** @var string|null */
	public $title;

	/** @var string|null */
	public $message;

	/** @var string */
	public $stdout = '';

	/** @var string */
	public $stderr = '';

	/** @var string */
	private $file;

	/** @var int */
	private $result = self::PREPARED;

	/** @var float|null */
	private $duration;

	/** @var string[]|string[][] */
	private $args = [];


	public function __construct(string $file, string $title = null)
	{
		$this->file = $file;
		$this->title = $title;
	}


	public function getFile(): string
	{
		return $this->file;
	}


	/**
	 * @return string[]|string[][]
	 */
	public function getArguments(): array
	{
		return $this->args;
	}


	public function getSignature(): string
	{
		$args = implode(' ', array_map(function ($arg): string {
			return is_array($arg) ? "$arg[0]=$arg[1]" : $arg;
		}, $this->args));

		return $this->file . ($args ? " $args" : '');
	}


	public function getResult(): int
	{
		return $this->result;
	}


	public function hasResult(): bool
	{
		return $this->result !== self::PREPARED;
	}


	/**
	 * Duration in seconds.
	 */
	public function getDuration(): ?float
	{
		return $this->duration;
	}


	/**
	 * @return static
	 */
	public function withArguments(array $args): self
	{
		if ($this->hasResult()) {
			throw new \LogicException('Cannot change arguments of test which already has a result.');
		}

		$me = clone $this;
		foreach ($args as $name => $values) {
			foreach ((array) $values as $value) {
				$me->args[] = is_int($name)
					? "$value"
					: [$name, "$value"];
			}
		}
		return $me;
	}


	/**
	 * @return static
	 */
	public function withResult(int $result, ?string $message, float $duration = null): self
	{
		if ($this->hasResult()) {
			throw new \LogicException("Result of test is already set to $this->result with message '$this->message'.");
		}

		$me = clone $this;
		$me->result = $result;
		$me->message = $message;
		$me->duration = $duration;
		return $me;
	}
}
