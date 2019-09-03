---
id: getting-started
title: Getting Started
weight: -1
---

Merlin Framework provides a standardised method for migrating content from a website (markup) to more structured data.

This allows for simplified migration of web content to a new CMS or other systems.


## Core concepts

The migration framework expects to take a YAML (.yml) file containing all the configuration required for a migration run. A separate migration configuration exists for each logical content structure split, for example these may be:
- Menus
- Content Type A
- Content Type B
- Taxonomy A
- Taxonomy B
- .. etc

Each configuration file contains a reference to either a website domain and list of URLs, or a path to relevant XML files (see [XML File Support]()).

Content from these sources are then passed through mappings, which take selectors (XPath or JQuery-like selectors) to map content from the DOM to the JSON file that gets generated during a run. These data values can also pass through processors to further refine and alter the data.

# Prerequisites
The framework requires PHP (latest recommended, but tested on most versions of 7.x) and composer. All other dependencies will be pulled in by running a `composer install`

# Running a migration
To run a migration simply run the tool with the input configuration .yml file, and a path to the output, e.g:

`php migrate generate -c /path/to/config.yml -o /path/to/output`

You will see output as follows:
```
Migration framework
===================

Preparing the configuration
---------------------------

 [OK] Done!

Processing requests
-------------------

Parsing... https://www.example.gov.au/health/conditionsandtreatments/Treating-persistent-pain (Done!)

  ... etc (x2000 pages)

Generating files
----------------

Generating /tmp/page_type.json Done!
Generating /tmp/error-not-found.json Done!
Generating /tmp/media-image-bhc_fact_sheet.json Done!
Generating /tmp/call_to_action.json Done!
Generating /tmp/content_partner.json Done!
Generating /tmp/fact_sheet.json Done!
Generating /tmp/error-404.json Done!
Generating /tmp/media-embedded_video-bhc_fact_sheet.json Done!

 [OK] Done!

Completed in 87.295419931412
```

## Running migration flags
Optionally override or specify options when running migrations by using migration flags. At minimum, you need to specify the `-c` flag. All other flags have defaults.
|Flag|Full Name|Description|Default|
| --- | --- | --- | --- |
| `-c` | `--config` | Path to the configuration file | |
| `-o` | `--output` | Path to the output directory | `__DIR__` |
| `-d` | `--debug` | Output debug messages | `false` |
| `-l` | `--limit` | Limit the max number of items to migrate (overrides the `maximum_total` config option if specified) | `0` (Crawl all items) |
| `--concurrency` | `--concurrency` | Number of requests to make in parallel | `10` |

## Refreshing JSON assets

The resulting JSON files are now ready to push into the Drupal Migration plugins. These files should be hosted somewhere that Drupal can access, e.g a web-accessible URL.

## Error handling and reporting

There are JSON files generated with error reporting included. These may include `error-not-found.json`, `error-404.json` and `error-unhandled.json`. These will indicate where selectors cannot find matches on any given page, or where a URL does not resolve (404, 500, or similar).

Warning files will also be generated containing further useful information about a run.

Duplicate content will be detected and tracked in `url-content-duplicates.json`.
