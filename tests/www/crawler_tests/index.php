<html>

<head>
  <title>PHP Test</title>
</head>

<body>
  <nav>
    <ul>
      <li><a href="/about.html">About</a></li>
      <li><a href="/home.html">Home</a></li>
      <li><a href="/search.php">Search</a></li>
      <li><a href="/index.php?p=1#fragment ">Test 1</a></li>
      <li><a href="/index.php?p=2">Test 2</a></li>
      <li><a href="/index.php?p=3">Test 3</a></li>
      <li><a href="/index.php?p=1">Duplicate link</a></li>
      <li><a href="/duplicate_links.php">Duplicate Links</a></li>
    </ul>
  </nav>
  <?php
  switch ($_GET['p']) {
    case 1:
      echo '<p>The first page</p>';
      break;
    case 2:
      echo '<p>The second page</p>';
      break;
    case 3:
      echo '<p>The third page</p>';
      break;
  }
  ?>
</body>

</html>
