<?php

$query = isset($_GET['random_content']) ? "?random_content=1" : null;

// Test redirect fetch (302)
header("Location: redirect_dst.php{$query}");

