---
id: crawler
title: URL Crawler
---

# URL Crawler

Merlin comes with a URL crawler to help generate URL lists prior to a migration.

## Configuration example
```
---
domain: htts://example.com/

options:
  follow_redirects: true  # Allow internal redirects.
  ignore_robotstxt: true  # Ignore robots.txt rules around crawlability.
  maximum_total: 0        # Optionally restrict total number of crawled URLs.
  concurrency: 10         # Restrict concurrent crawlers.
  rewrite_domain: true    # Standardises base domain.
  delay: 100              # Pause between requests in ms.
  exclude: []             # Regex matches to exclude.
```

Simply provide a configuration input file and output folder for generated assets and run with:
`php migrate crawl -c /path/to/config.yml -o /path/to/output`

You will see output as follows:

```
===================
Preparing the configuration
---------------------------

Setting concurrency to 10

 [OK] Starting crawl!

https://example.com/
https://example.com/sports/cricket
https://example.com/sports/basketball
https://example.com/sports/baseball
https://example.com/products/classic-teamwear
https://example.com/sports/afl
https://example.com/sports
https://example.com/products
... etc

 [ERROR] Error: https://example.com/design -- Found on url: https://example.com/

 [OK] Done!
 [OK] 432 total URLs.

Generating files
----------------

Generating /tmp/crawled-urls.yml Done!
Generating /tmp/crawl-error.yml Done!

 [OK] Done!

Completed in 29.005132913589
```
