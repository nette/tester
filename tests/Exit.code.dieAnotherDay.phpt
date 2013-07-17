<?php

/**
 * @exitCode 231
 */

require __DIR__ . '/bootstrap.php';


register_shutdown_function(function(){
	die(231);
});

// die(0);   - under CLI exit code will be 0 in PHP 5.4.0 - 5.4.6 due PHP bug #62725
// die(255); - under CLI exit code will be 255 in PHP 5.3.0 - 5.5.0 due PHP bug #65275
