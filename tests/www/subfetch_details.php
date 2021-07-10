<?php

$pid = $_GET['pid'];
$title = "Super Product Number {$pid}";
$description = "This is Super Product Number {$pid} Description";
$price = "{$pid}.00";

?>

<html>

<head>
  <title>Subfetch Landing - <?php echo $title; ?></title>
</head>

<body>

<div class="title"><strong>Title:</strong><br><span><?php echo $title; ?></span></div>
<div class="description"><strong>Description:</strong><br><span><?php echo $description; ?></span></div>
<div class="price"><strong>Price:</strong><br><span><?php echo $price; ?></span></div>

</body>

</html>
