<?php

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? USER_AGENT_FAILED;

?>

<html>

<head>
  <title>User Agent Test</title>
</head>

<body>

	<h1><?php echo $userAgent; ?></h1>

</body>

</html>
