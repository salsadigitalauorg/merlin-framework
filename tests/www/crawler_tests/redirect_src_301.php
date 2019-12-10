<?php

$query = isset($_GET['random_content']) ? "?random_content=1" : null;

// Test redirect fetch (301)
header("HTTP/1.1 301 Moved Permanently");
header("Location: redirect_dst.php{$query}");
