<?php

declare(strict_types=1);


function beforeEach(?Closure $fn): void
{
	Tester\Environment::$testFunction->beforeEach($fn);
}


function afterEach(?Closure $fn): void
{
	Tester\Environment::$testFunction->afterEach($fn);
}


function test(string $description, Closure $fn): void
{
	Tester\Environment::$testFunction->test($description, $fn);
}
