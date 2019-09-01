<?php

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'No HTTP_USER_AGENT set in _SERVER vars!';

?>

<html>

<head>
  <title>User Agent Test</title>
</head>

<body>

	<h1><?php echo $userAgent; ?></h1>

</body>

</html>
