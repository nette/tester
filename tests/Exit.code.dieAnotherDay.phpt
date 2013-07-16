<?php

/**
 * @exitCode 231
 */

require __DIR__ . '/bootstrap.php';


register_shutdown_function(function(){
	die(231);
});
