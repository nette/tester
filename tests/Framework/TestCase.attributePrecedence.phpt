<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Attributes\DataProvider;
use Tester\Attributes\Throws;

require __DIR__ . '/../bootstrap.php';


class MyTest extends Tester\TestCase
{
	public $order;


	public function attrProvider()
	{
		return ['attr' => [1, 2]];
	}


	public function docProvider()
	{
		return ['doc' => [3, 4]];
	}


	/**
	 * The attribute wins; the docblock @throws is ignored.
	 * @throws LogicException
	 */
	#[Throws(RuntimeException::class)]
	public function testThrowsAttributeWins()
	{
		throw new RuntimeException;
	}


	/**
	 * The attribute wins; the docblock @dataProvider is ignored.
	 * @dataProvider docProvider
	 */
	#[DataProvider('attrProvider')]
	public function testProviderAttributeWins($a, $b)
	{
		$this->order[] = func_get_args();
	}


	#[Throws(RuntimeException::class)]
	#[Throws(LogicException::class)]
	public function testDuplicateThrows()
	{
	}
}


// #[Throws] overrides docblock @throws
$test = new MyTest;
$test->runTest('testThrowsAttributeWins');

// #[DataProvider] overrides docblock @dataProvider
$test = new MyTest;
$test->runTest('testProviderAttributeWins');
Assert::same([[1, 2]], $test->order);

// #[Throws] may be specified only once
Assert::exception(
	fn() => (new MyTest)->runTest('testDuplicateThrows'),
	Tester\TestCaseException::class,
	'Attribute #[Throws] for testDuplicateThrows() can be specified only once.',
);
