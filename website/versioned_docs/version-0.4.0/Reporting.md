---
id: version-0.4.0-reporting
title: Migration Reporting
original_id: reporting
---

Merlin has a reporting tool that allows you to verify the success or failure of a migration in a simple way.

The ``report`` command can check that the initial URL list exists on a new target domain, verify the headers received are the same as the original URL as well as verify any media exist (e.g. documents etc).

**Note:** currently reporting does not verify the existence of the *body content* from on the destination domain, or that it matches the original source, only that the URLs exist and returns the expected header.



## Reporting Configuration

Reports can be generating by creating a reports YAML configuration file.  You can create any number of reports from a migration through the configuration.


### Available Report Types
Currently there are two report types available - `content` (i.e. the original URL list) and `media`.  

If you have multiple YAML or JSON configuration or result files you can generate multiple reports (e.g. different entity URL configurations or media JSON results).


#### Content

The report type `content` is for verifying a list of URLs exists on the destination domain.  The source of this list can be specified as the original migration YAML crawler configuration used to migrate the site.

If this migration is cached from a crawl the reporting will check the cached version headers against the destination to detect any possible mismatch.


#### Media

The report type `media` is for verifying any media detected in a migration.  The reporting tool uses the results of the media migration json files generated during a migration run as the source list to verify.   


## Reporting Configuration Example

See the table below for an explanation of each configuration directive.

```
---

reports:
  - 
    type: content
    dst_domain: http://localhost:8000
    src_domain: http://localhost:8000    
    url_source: path/to/a/migration/config_for_wundersite.yml

    # All options are... optional.
    options:
    
      verify_source_response: false
      adopt_source_name: true
      # filename_suffix: "let_a_thousand_blossoms_bloom"
      # filename_root: "manually-specify-output-filename"
    
      report_options:
        title: "A custom title"
        adopt_title_name: true
        save_json: true
        save_html: true
        save_pdf: true
        
  - 
    type: media
    enabled: true
    src_domain: http://localhost:8000
    dst_domain: http://localhost:8000
    url_source: path/to/a/media/results-file.json
    # url_source: path/to/a/pattern/media-*-files.json
    # url_source:
    #  - path/to/media/file-1.json
    #  - path/to/media/file-2.json
    #  - path/to/media/file-3.json
    
    options:
      verify_source_response: true
      report_options:
        save_pdf: false    
      rewrite_urls:
        domain: https://dfat.gov.au
        rewrite: true
        path: /sites/default/files
      
        
```


### Configuration 

The first group of main options are required and set key report variables.  The second group of options are not required and set the network configuration for checking requests.

|Directive|Required|Description|Default
| --- | --- | --- | --- |
| **Main Configuration** | | | |
| `type` | Required `string` | Type (i.e. class) of report. | `'content'` |
| `url_source` | Required `string` | The source file that contains the URLs to check.  This can be: A path to a single file, an array list of multiple files or a pattern/wildcard match path to multiple files.  |  |
| `dst_domain` | Required `string` | The destination domain of the migrated content. |  |
| `src_domain` | optional `string` | The source domain of the migrated content.  Note that if you wish to verify source responses (see options below) you must specify this. |  |
| `enabled` | optional `bool` | If Merlin should generate this report. | `true` |
| `options` | optional `array`| Options for the report build. | See Configuration (options) below. |
| **Network Configuration** | | | |
| `concurrency ` | optional `int`| Concurrent cURL requests. | `10`|
| `follow_redirects ` | optional `bool`| Follow redirect headers. | `true`|
| `max_redirects ` | optional `int`| Maximum redirects. | `5`|
| `ignore_ssl_errors ` | optional `int`| Ignore any SSL errors encountered. | `false`|
| `timeouts` | optional `array`| Various timeouts, see below. | |
| `timeouts['connect_timeout'] ` | optional `int`| Connection timeout in seconds. | `10`|
| `timeouts['timeout'] ` | optional `int`| Overall timeout in seconds. | `30`|



### Configuration (options)
These options sit under the `options` array in the configuration.

|Directive|Required|Description|Default
| --- | --- | --- | --- |
| `verify_source_response ` | Optional `bool` | Enables source header checking and compares against destination.  | `false` |
| `adopt_source_name ` | Optional `bool` | This builds report result files based on the input source filename.  This facilitates automatic file naming and report titling.  If you don't use it you will most likely want to use the `filename_suffix` option. | `true` |
| `filename_root ` | Optional `string` | Filenames will not be automatically generated but will instead be based on the string specified.<br><br><strong>NOTE:</strong>  This options overrides `adopt_source_name` and `report_options.adopt_title_name`| |
| `filename_suffix ` | Optional `string` | If you don't use the above `adopt_source_name` option, you will need to manually specify a filename suffix if you have multiple reports of the same type so they don't overwrite each other, either by using this option or `filename_root`. |  |
| `rewrite_urls ` | Optional `array` | This set of options will rewrite the original source urls according to some rules when requests are made to the destination server.  | See below  |
| `report_options ` | Optional `array` | Options pertaining to the saved output of the reports. | See below  |


### Configuration (rewrite_urls)
`rewrite_urls` sits under the `options` array in the configuration.

|Directive|Required|Description|Default
| --- | --- | --- | --- |
| `rewrite ` | Optional `bool` | Enable/disable the rewrite | `false` |
| `domain ` | Optional `string` | Domain to use in the rewrite. | `dst_domain` |
| `path ` | Optional `string` | Path to use in the rewrite.  All URLs will use the path specified infront of the base filename. | The original path. |


### Configuration (report_options)
`report_options` sit under the `options` array in the configuration.

|Directive|Required|Description|Default
| --- | --- | --- | --- |
| `title ` | Optional `string` | Report title. | Source file name<br>if `adopt_source_name` is `true`<br>"Migration Report"<br>if `adopt_source_name` is `false` |
| `adopt_title_name ` | Optional `bool` | Generated filenames will be based on the report title instead of the source input files. | `true` |
| `save_json ` | Optional `bool` |  Save the report raw results as JSON. | `true` |
| `save_html ` | Optional `bool` |  Save the HTML version of the report. | `true` |
| `save_pdf ` | Optional `bool` |  Save the PDF version of the report. | `false` |
| `paper_size ` | Optional `string` | Paper size for the PDF. | `A4` |
| `paper_orientation ` | Optional `string` | Paper orientation for the PDF. | `landscape` |



## Creating the Report

      
Simply provide a configuration input file and output folder for generated reports and run with:

```
php migrate report -c /path/to/reporting_config.yml -o /path/to/output
```

You will see output something like:

```
Migration Reporting
===================

Generating content report for http://localhost:8000
---------------------------------------------------

Checking 14 URLs...
Checked dst url: http://localhost:8000/
Checked src url: http://localhost:8000/
Checked dst url: http://localhost:8000/about.html
Checked src url: http://localhost:8000/about.html
Checked dst url: http://localhost:8000/home.html
Checked src url: http://localhost:8000/home.html
Checked dst url: http://localhost:8000/search.php
Checked src url: http://localhost:8000/search.php
Checked dst url: http://localhost:8000/index.php?p=1
...

 [OK] URL Check complete

Munging data...

 [OK] Munge complete!

 [OK] Wrote JSON data file: /tmp/merlin-report-content-93-fetcher-redirect-support.json

 [OK] Wrote HTML report file: /tmp/merlin-report-content-93-fetcher-redirect-support.html

 [OK] Wrote PDF report file: /tmp/merlin-report-content-93-fetcher-redirect-support.pdf
```

## CLI Flag Options
|Flag|Full Name|Description|Default|
| --- | --- | --- | --- |
| `-c` | `--config` | Path to the reporting configuration file | |
| `-o` | `--output` | Path to the output directory to write reports | `/tmp` |

