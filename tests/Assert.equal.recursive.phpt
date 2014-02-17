<?php

/**
 * @testCase
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


class RecursionTest extends Tester\TestCase
{
	public function testSimple()
	{
		$o1 = new stdClass;
		$o2 = new stdClass;
		$o1->a = $o1;
		$o2->a = $o2;
		$o1->b = 'foo';
		$o2->b = 'foo';
		Assert::equal( $o1, $o2 );


		$o1 = new stdClass;
		$o2 = new stdClass;
		$o1->a = $o1;
		$o2->a = $o2;
		$o1->b = 'foo';
		$o2->b = 'bar';
		Assert::notEqual( $o1, $o2 );


		$o1 = new stdClass;
		$o2 = new stdClass;
		$o1->a = $o1;
		$o2->a = new stdClass;
		Assert::notEqual( $o1, $o2 );


		$o1 = new stdClass;
		$o2 = new stdClass;
		$o1->a = new stdClass;
		$o2->a = new stdClass;
		$o1->a->a = $o1;
		$o2->a->a = $o2->a;
		Assert::notEqual( $o1, $o2 );
	}


	public function testMultiple()
	{
		$o1 = new stdClass;
		$o2 = new stdClass;
		$o1->a = $o1;
		$o2->a = $o2;
		$o1->b = $o1;
		$o2->b = $o2;
		$o1->c = $o1;
		$o2->c = $o2;
		Assert::equal( $o1, $o2 );

		$o2->c = $o1;
		Assert::notEqual( $o1, $o2 );
	}


	public function testDeep()
	{
		$o1 = new stdClass;
		$o2 = new stdClass;
		$o1->a = new stdClass;
		$o2->a = new stdClass;
		$o1->a->b = new stdClass;
		$o2->a->b = new stdClass;
		$o1->a->b->c = new stdClass;
		$o2->a->b->c = new stdClass;
		$o1->a->b->c->d = $o1;
		$o2->a->b->c->d = $o2;
		Assert::equal( $o1, $o2 );

		$o2->a->b->c->d = $o1;
		Assert::notEqual( $o1, $o2 );
	}


	public function testCross()
	{
		$o1 = new stdClass;
		$o2 = new stdClass;
		$o1->a = $o2;
		$o2->a = $o1;
		Assert::equal( $o1, $o2 );

		$o3 = new stdClass;
		$o3->a = $o3;
		$o2->a = $o3;
		Assert::notEqual( $o1, $o2 );


		$o1 = new stdClass;
		$o2 = new stdClass;
		$o1->a = new stdClass;
		$o2->b = new stdClass;
		$o1->b = $o1;
		$o2->a = $o2;
		Assert::notEqual( $o1, $o2 );

		$o1->b = $o1->a;
		$o2->a = $o2->b;
		Assert::equal( $o1, $o2 );
	}


	public function testThirdParty()
	{
		$o1 = new stdClass;
		$o2 = new stdClass;
		$o3 = new stdClass;
		$o1->a = $o3;
		$o2->a = $o3;
		$o1->b = $o1->a;
		$o2->b = $o2->a;
		Assert::equal( $o1, $o2 );


		$o1 = new stdClass;
		$o2 = new stdClass;
		$o3 = new stdClass;
		$o1->a = new stdClass;
		$o2->a = new stdClass;
		$o1->a->b = $o3;
		$o2->a->b = $o3;
		Assert::equal( $o1, $o2 );

		$o3->c = 'foo';
		Assert::equal( $o1, $o2 );

		$o3->c = $o1;
		Assert::equal( $o1, $o2 );
	}

}

$test = new RecursionTest;
$test->run();
