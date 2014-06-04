<?php

// fixture for HttpClient test

header('Content-Type: text/html; charset=windows-1250');
header('X-Love: Nette');

?>
<h1>Hello <?php echo $_SERVER['REQUEST_METHOD'] ?></h1>

Data: <?php echo json_encode($_POST) ?>.
