<?php

$randomContent = $_GET['random_content'] ?? null;
$html = "";
if ($randomContent) {
  for ($i=0; $i<5; $i++) {
    $html .=  "<p>" . substr(md5(rand()), 0, 10) . "</p>";
  }
  $html = "<div id='random_content'>{$html}</div>";
}

?>

<html>

<head>
  <title>Redirect Test</title>
</head>

<body>
<h1>Redirect Successful</h1>

<h2></h2>

<?php
echo $html
?>

</body>

</html>
