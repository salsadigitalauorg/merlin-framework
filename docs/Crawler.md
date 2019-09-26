---
id: crawler
title: URL Crawler
---

Merlin comes with a URL crawler to help generate URL lists prior to a migration.

## URL Grouping

Merlin can assist in grouping URLs by specifying grouping options in the crawler config. This is useful to crawl an entire website and generate separate lists of URLs based on a set of criteria (either regex, or selectors in the DOM).

All groupings should have an `id` key as this will be used to key the result set.

### Available types

#### Path

Group URLs based on their path. This grouping method allows you to specify wildcard path patterns to match certain groups. You can use one or more patterns when defining this rule. The matching method allows wildcard (`*`) in the URL, this can be used to match the whole path or parts in the path.

```
type: path
options:
  pattern: /single-pattern
  # pattern:
  #    - /multiple-patterns/*
  #    - /works/*/with/wildcards/*/in/pattern
```

#### Element

Group URLs by the existence of a DOM element. The selector option can be a valid CSS selector or Xpath selector.

```
type: element
options:
  selector: //*/h1
```

#### Value

Group URLs by the value of a given element or attribute. If the `attribute` key is not present it will use the text value of DOM node when doing the comparison. Pattern can be a simple string or a valid regular expression.

```
type: element
options:
  selector: //*/h1
  pattern: /\w+{4}/
  attribute: data-type
```

## Configuration example
```
---
domain: https://example.com/

options:
  follow_redirects: true  # Allow internal redirects.
  ignore_robotstxt: true  # Ignore robots.txt rules around crawlability.
  maximum_total: 0        # Optionally restrict total number of crawled URLs.
  concurrency: 10         # Restrict concurrent crawlers.
  rewrite_domain: true    # Standardises base domain.
  delay: 100              # Pause between requests in ms.
  exclude: []             # Regex matches to exclude.
  path_only: true         # Return only the path from the crawled URL.

  # Optionally provide one or more URLs as the starting point. This can be provided
  # as a string or an array.
  urls:                   
    - /sports/cricket
  
  # Group options to allow segmenting URL basede on some business rules.
  group_by: []             
  
  # Caches crawled content and uses cache to build results.
  cache_enabled: true     
 
  # Generates a list of urls containing duplicate content.  Only the first
  # crawled duplicate will appear in the crawled url or grouped url results. 
  find_content_duplicates: true   
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

## Flag Options
|Flag|Full Name|Description|Default|
| --- | --- | --- | --- |
| `-c` | `--config` | Path to the configuration file | |
| `-o` | `--output` | Path to the output directory | `__DIR__` |
| `-d` | `--debug` | Output debug messages | `false` |
| `-l` | `--limit` | Limit the max number of items to crawl (overrides the `maximum_total` config option if specified) | `0` (Crawl all items) |
| | `--concurrency` | Number of requests to make in parallel | `10` |
| | `--no-cache` | Run without cache | `false` (Cache enabled) |
