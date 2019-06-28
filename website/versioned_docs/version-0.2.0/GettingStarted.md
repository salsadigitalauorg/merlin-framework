---
id: version-0.2.0-getting-started
title: Getting Started
weight: -1
original_id: getting-started
---

The migration tool provides a standard mechanism for scraping content from DHHS websites, split into logical content structures, and perform additional processing to ensure a result ready for import into Drupal.

- Initial code is available on https://github.com/salsadigitalauorg/merlin-framework
- As this codebase is likely to be open-sourced and see ongoing development effort the branch `<TBD>` is the safest to use with DHHS migration configurations


# Core concepts

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

`php migrate generate -c configs/bhc/fact_sheet.yml -o /path/to/output/`

You will see output as following:
```
Migration framework
===================

Preparing the configuration
---------------------------

 [OK] Done!

Processing requests
-------------------

Parsing... https://www.betterhealth.vic.gov.au/health/conditionsandtreatments/Treating-persistent-pain (Done!)

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

## Refreshing JSON assets

The resulting JSON files are now ready to push into the Drupal Migration plugins. These files should be hosted somewhere that Drupal can access, e.g a web-accessible URL.

## Error handling

There are JSON files generated with error reporting included. These may include `error-not-found.json`, `error-404.json` and `error-unhandled.json`. These will indicate where selectors cannot find matches on any given page, or where a URL does not resolve (404, 500, or similar).
