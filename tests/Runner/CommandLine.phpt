<?php

use Tester\Assert,
	Tester\Runner\CommandLine as Cmd;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../../Tester/Runner/CommandLine.php';



test(function() {
	$cmd = new Cmd('
		-p
		--p
		--a-b
	');

	Assert::same( array('-p' => NULL, '--p' => NULL, '--a-b' => NULL), $cmd->parse(array()) );
	Assert::same( array('-p' => TRUE, '--p' => NULL, '--a-b' => NULL), $cmd->parse(array('-p')) );

	$cmd = new Cmd('
		-p  description
	');

	Assert::same( array('-p' => NULL), $cmd->parse(array()) );
	Assert::same( array('-p' => TRUE), $cmd->parse(array('-p')) );
});


test(function() { // default value
	$cmd = new Cmd('
		-p  (default: 123)
	');

	Assert::same( array('-p' => '123'), $cmd->parse(array()) );
	Assert::same( array('-p' => TRUE), $cmd->parse(array('-p')) );


	$cmd = new Cmd('
		-p
	', array(
		'-p' => array(Cmd::VALUE => 123),
	));

	Assert::same( array('-p' => 123), $cmd->parse(array()) );
	Assert::same( array('-p' => TRUE), $cmd->parse(array('-p')) );
});


test(function() { // alias
	$cmd = new Cmd('
		-p | --param
	');

	Assert::same( array('--param' => NULL), $cmd->parse(array()) );
	Assert::same( array('--param' => TRUE), $cmd->parse(array('-p')) );
	Assert::same( array('--param' => TRUE), $cmd->parse(array('--param')) );
	Assert::same( array('--param' => TRUE), $cmd->parse(explode(' ', '-p --param')) );
	Assert::exception(function() use ($cmd) {
		$cmd->parse(array('-p=val'));
	}, 'Exception', 'Option --param has not argument.');

	$cmd = new Cmd('
		-p --param
	');

	Assert::same( array('--param' => TRUE), $cmd->parse(array('-p')) );

	$cmd = new Cmd('
		-p, --param
	');

	Assert::same( array('--param' => TRUE), $cmd->parse(array('-p')) );
});


test(function() { // argument
	$cmd = new Cmd('
		-p param
	');

	Assert::same( array('-p' => NULL), $cmd->parse(array()) );
	Assert::same( array('-p' => 'val'), $cmd->parse(explode(' ', '-p val')) );
	Assert::same( array('-p' => 'val'), $cmd->parse(explode(' ', '-p=val')) );
	Assert::same( array('-p' => 'val2'), $cmd->parse(explode(' ', '-p val1 -p val2')) );

	Assert::exception(function() use ($cmd) {
		$cmd->parse(array('-p'));
	}, 'Exception', 'Option -p requires argument.');

	Assert::exception(function() use ($cmd) {
		$cmd->parse(array('-p', '-a'));
	}, 'Exception', 'Option -p requires argument.');


	$cmd = new Cmd('
		-p=<param>
	');

	Assert::same( array('-p' => 'val'), $cmd->parse(explode(' ', '-p val')) );
});



test(function() { // optional argument
	$cmd = new Cmd('
		-p [param]
	');

	Assert::same( array('-p' => NULL), $cmd->parse(array()) );
	Assert::same( array('-p' => TRUE), $cmd->parse(array('-p')) );
	Assert::same( array('-p' => 'val'), $cmd->parse(explode(' ', '-p val')) );


	$cmd = new Cmd('
		-p param
	', array(
		'-p' => array(Cmd::VALUE => 123),
	));

	Assert::same( array('-p' => 123), $cmd->parse(array()) );
	Assert::same( array('-p' => TRUE), $cmd->parse(array('-p')) );
	Assert::same( array('-p' => 'val'), $cmd->parse(explode(' ', '-p val')) );


	$cmd = new Cmd('
		-p param
	', array(
		'-p' => array(Cmd::OPTIONAL => TRUE),
	));

	Assert::same( array('-p' => NULL), $cmd->parse(array()) );
	Assert::same( array('-p' => TRUE), $cmd->parse(array('-p')) );
	Assert::same( array('-p' => 'val'), $cmd->parse(explode(' ', '-p val')) );
});



test(function() { // repeatable argument
	$cmd = new Cmd('
		-p [param]...
	');

	Assert::same( array('-p' => array()), $cmd->parse(array()) );
	Assert::same( array('-p' => array(TRUE)), $cmd->parse(array('-p')) );
	Assert::same( array('-p' => array('val')), $cmd->parse(explode(' ', '-p val')) );
	Assert::same( array('-p' => array('val1', 'val2')), $cmd->parse(explode(' ', '-p val1 -p val2')) );
});



test(function() { // enumerates
	$cmd = new Cmd('
		-p <a|b|c>
	');

	Assert::same( array('-p' => NULL), $cmd->parse(array()) );
	Assert::exception(function() use ($cmd) {
		$cmd->parse(array('-p'));
	}, 'Exception', "Option -p requires argument.");
	Assert::same( array('-p' => 'a'), $cmd->parse(explode(' ', '-p a')) );
	Assert::exception(function() use ($cmd) {
		$cmd->parse(explode(' ', '-p foo'));
	}, 'Exception', 'Value of option -p must be a, or b, or c.');


	$cmd = new Cmd('
		-p [a|b|c]
	');

	Assert::same( array('-p' => NULL), $cmd->parse(array()) );
	Assert::same( array('-p' => TRUE), $cmd->parse(array('-p')) );
	Assert::same( array('-p' => 'a'), $cmd->parse(explode(' ', '-p a')) );
	Assert::exception(function() use ($cmd) {
		$cmd->parse(explode(' ', '-p foo'));
	}, 'Exception', 'Value of option -p must be a, or b, or c.');
});



test(function() { // realpath
	$cmd = new Cmd('
		-p <path>
	', array(
		'-p' => array(Cmd::REALPATH => TRUE),
	));

	Assert::exception(function() use ($cmd) {
		$cmd->parse(array('-p', 'xyz'));
	}, 'Exception', "File path 'xyz' not found.");
	Assert::same( array('-p' => __FILE__), $cmd->parse(array('-p', __FILE__)) );
});



test(function() { // positional arguments
	$cmd = new Cmd('', array(
		'pos' => array(),
	));

	Assert::same( array('pos' => 'val'), $cmd->parse(array('val')) );

	Assert::exception(function() use ($cmd) {
		$cmd->parse(array());
	}, 'Exception', 'Missing required argument <pos>.');

	Assert::exception(function() use ($cmd) {
		$cmd->parse(array('val1', 'val2'));
	}, 'Exception', 'Unexpected parameter val2.');

	$cmd = new Cmd('', array(
		'pos' => array(Cmd::REPEATABLE => TRUE),
	));

	Assert::same( array('pos' => array('val1', 'val2')), $cmd->parse(array('val1', 'val2')) );


	$cmd = new Cmd('', array(
		'pos' => array(Cmd::OPTIONAL => TRUE),
	));

	Assert::same( array('pos' => NULL), $cmd->parse(array()) );


	$cmd = new Cmd('', array(
		'pos' => array(Cmd::VALUE => 'default', Cmd::REPEATABLE => TRUE),
	));

	Assert::same( array('pos' => array('default')), $cmd->parse(array()) );
});



test(function() { // errors
	$cmd = new Cmd('
		-p
	');

	Assert::exception(function() use ($cmd) {
		$cmd->parse(array('-x'));
	}, 'Exception', 'Unknown option -x.');

	Assert::exception(function() use ($cmd) {
		$cmd->parse(array('val'));
	}, 'Exception', 'Unexpected parameter val.');
});
