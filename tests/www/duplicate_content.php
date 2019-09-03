<?php

$rand = rand();
$time = microtime(true);
$dynamicContent = "<!-- This comment should be ignored by the duplicate hash function {$rand} {$time} -->";

?>

<html>

<head>
  <title>Duplicate Content Test</title>
</head>

<body>

	<h1>Duplicate Bananas</h1>
	<p>Daddy Pig loves bananas</p>
	<p>Everyone loves bananas!</p>
	<?php echo $dynamicContent ?>

</body>

</html>
