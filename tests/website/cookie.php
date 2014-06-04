<?php

// fixture for HttpClient test
$counter = & $_COOKIE['counter'];
$counter++;
setcookie('counter', $counter);

echo $counter;
