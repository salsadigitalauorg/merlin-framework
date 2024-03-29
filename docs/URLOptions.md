---
id: url-options
title: URL and Fetcher Options
---

# URL and Fetcher options

URL content is retrieved via a Fetcher.  There are a number of options that can apply to how the content is retreived.  These options are specified by the `fetch_options` array directive in the configuration:

| Option        | Type | Default | Description           |
| ------------- | ---- | ------- | ------------- |
| `concurrency` | int | `10` | How many maximum concurrent requests should be used to fetch content.
| `delay` | int | `100` | Delay between requests in milliseconds.
| `cache_enabled` | boolean | `true` | If enabled, URL content is cached on disk for subsequent processing.
| `cache_dir` | string | `/tmp/merlin_cache` | Directory to store the cache.  If the path does not exist it will be created.
| `fetcher_class` | string | `FetcherCurl` | The full name-spaced class name of the Fetcher class to use to retrieve content.  In most normal circumstances this can be left alone.
| `execute_js` | boolean | false | **FetcherSpatieCrawler Only:**<br>Executes javascript on the page after fetching.  You need to ensure the necessary node dependencies are met and installed.
| `follow_redirects` | boolean | `true` | If enabled, redirects e.g. `302` will be followed.
| `ignore_ssl_errors` | boolean | `false` | If enabled, will ignore SSL errors.
| `user_agent` | string | "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36" | Set the User-Agent to identify as in requests.
| `referer` | string | *NULL* | Domain/URL to use as referer for the request.
| `ip_resolve` | string | `whatever` | **FetcherCurl Only:**<br>IP address version resolve for cURL.  Depending on the system, using single version (e.g. v4) alone can speed up requests significantly.  Valid values: `v4`, `v6`, `whatever`.  `whatever` will use all allowed IP versions on the system.
| `timeouts` | array | `connect_timeout: 15`<br>`timeout: 15`<br>`read_timeout: 30`<br> | Various fetching timeouts.  Note that if you use `execute_js` these timeouts will need to be at least doubled to allow time to run the javascript.
Most of the time the default Fetcher class should cover most usage requirements, however, you can specify a custom class name if you need to do something different.  Check `Merlin\Fetcher\Fetchers\*` for examples of how to implement.

## Example `fetcher_options` configuration

```
---
domain: http://www.example.com

urls:
  - /some/path
  - /some/path?with=a&query=123
  - /some/path?with=a&query=123#and-a-fragment

fetch_options:
  # Default 10
  concurrency: 10

  # Delay between requests, default 100 milliseconds
  delay: 100

  # Cache content (and use previously cached content), default true
  cache_enabled: true

  # Cache storage root dir (path created if doesn't exist), default /tmp/merlin_cache
  cache_dir: '/tmp/merlin_cache'

  # Fetcher class, default FetcherCurl
  # fetcher_class: '\Merlin\Fetcher\Fetchers\SpatieCrawler\FetcherSpatieCrawler'
  fetcher_class: '\Merlin\Fetcher\Fetchers\Curl\FetcherCurl'
  # fetcher_class: '\Merlin\Fetcher\Fetchers\RollingCurl\FetcherRollingCurl'

  # Execute on-load JS, default false.
  # Currently only available if using the FetcherSpatieCrawler fetcher class
  execute_js: false

  # Whether to follow redirects
  follow_redirects: true

  # Ignore SSL errors
  ignore_ssl_errors : true
  
  # Address IP resolve version 
  ip_resolve: 'v4' 

  # Timeouts.  When using execute_js, you want to have reasonably long timeouts.
  # Not all timeouts are applicable to all Fetchers.
  timeouts:
    connect_timeout: 15,
    timeout: 60,
    # FetcherSpatieCrawler only
    read_timeout: 30

```


# URL Options

There are a number of options that can apply to the URL list.  These options are specified by the `url_options` array directive in the configuration:


| Option        | Description   | Default |
| ------------- | ------------- | ------------- |
| `include_query` | Will include the **query** part of the URL in the request.  If set to false, the crawler will only fetch the path component of the URL. | boolean `false` |
| `include_fragment` | Will include the **fragment** part of the URL.  If set to false, the crawler will only fetch the path component of the URL. | boolean `false` |
| `find_content_duplicates ` | Will check for **content** duplicates.  This will create a file called `url-content-duplicates.json` that contains a list of URLs that appear to resolve to the same content.  This is to avoid content duplication in the target system as well as provide a way to easily generate aliases. | boolean `true` |
| `count_redirects_as_content_duplicates` | Will also check redirects for content duplicates. If the `hash_selector` is set to body, *all* redirects will be counted as the duplicates of a single hash due to redirects do not have body content. | boolean `true` |
| `hash_selector` | This is an **XPath** selector that is used to generate the hash of content that is used to detect duplicates.  By default `sha1` is used as the hash algorithm and uses the `<body>` tag of the page as the determining content.| string `"//body"` |
| `hash_exclude_nodes ` | This is an array of **XPath** selectors to *exclude* when generating the hash to detect duplicates.  This could include elements that may appear on the page that might be metadata/cache busters or contain timestamps etc that can be safely excluded from building a hash for duplicate detection.  By default all `<script>`, `<!-- Comment -->`, `<style>`, `<input>` and `<head>` tags will be ignored. | array |
| `urls` | This is an associative array of urls and their corresponding `include_query` and `include_fragment` settings (as above) to override the global setting, if required.| array |
| `raw_strip_script_tags` | Uses a regular expression on the raw fetched content to strip script tags before being read by the DOM library.  These script tags can sometimes cause unexpected rewriting by the library if it considers them to be non-conforming markup.  | `false` |
| `raw_pattern_replace` | Associative array with keys `pattern` and `replace`.  Uses a regular expression on the raw fetched content to do a search and replace.  You must specify both keys for it to be enabled. | array |


## Example `url_options` configuration

```
---
domain: http://www.example.com

urls:
  - /some/path
  - /some/path?with=a&query=123
  - /some/path?with=a&query=123#and-a-fragment

url_options:
  # Default false
  include_query: true

  # Default false
  include_fragment: true

  # Default true
  find_content_duplicates: true

  # Default '//body'
  hash_selector: '//body'

  # Default script, comment, style, input, head
  hash_exclude_nodes:
    - '//script'
    - '//comment()'
    - '//style'
    - '//input'
    - '//head'

  # Strip <script> tags and replace all tables
  raw_strip_script_tags: true
  raw_pattern_replace:
    pattern: '#<table(.*?)>(.*?)</table>#is'
    replace: '<table class="was-a-table"></table>'
```

### Example overriding options for specific URLs

If there are URLs that need to have a different query or fragment inclusion setting from that of the global setting, their behaviour can be specified independently:

```
---
domain: http://www.example.com

urls:
  - /some/path
  - /some/path?with=a&query=123
  - /some/path?with=a&query=123#and-a-fragment

url_options:
  include_query: false
  include_fragment: false
  urls:
    -
      url: /some/path?with=a&query=123#and-a-fragment
      include_query: true
      include_fragment: true

```

## URLs in separate file(s)

You can also provide a list of URLs in one or more separate file(s). Your configuration can provide both `urls` and `urls_file` properties, or just one. Supply the `urls_file` relative paths to the config files. You can provide `urls_file` as a string for just one file or as an array if you have more than one list of URLs.

```
---
domain: http://www.example.com

urls:
 - /some/path
 - /some/path/subpath

urls_file: list_of_urls.yml
```

### Example of multiple urls_file

```
---
domain: http://www.example.com

urls:
 - /some/path
 - /some/path/subpath

urls_file:
 - list_of_urls.yml
 - another_list_of_urls.yml
```

### Example of a separate URLs file

Provide the list of urls in a separate file with a single `urls` property that contains the list of URLs. Example content of a `urls_file`:

```
---
urls:
  - /some/path
  - /some/other/path
```
