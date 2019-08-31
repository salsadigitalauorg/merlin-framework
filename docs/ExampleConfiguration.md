---
id: examples
title: Examples
---

# Menu

Menu structures use the `menu_link` type. This sample configuration demonstrates how to pull the main menu from the Health.vic site, with parent/child relationships in-tact.

The selector uses an Xpath to reference the element in the DOM, in this case all list-items contained in the header nav are evaluated for top level links. The `text` and `link` options are sub-selectors to help define where link text and link values should come from.

The `children` section allows for sub-menu items to be defined via their own `selector` and configuration.

```
---
domain: https://www2.health.vic.gov.au

urls:
  - /

entity_type: menus

mappings:
  -
    field: main_menu
    name: health_main_menu
    type: menu_link
    selector: '//*[@class="header-nav"]/*/ul/li'
    options:
      text: './a'
      link: './a/@href'
      remove_duplicates: true
    children:
      -
        type: menu_link
        selector: './descendant::li[@class="dd-level2"]'
        options:
          text: './a/h3'
          link: './a/@href'
```

# URL aliases

The URL alias of each content should be preserved so URLs can remain in-tact when migrated into the destination CMS. Simply attach the `alias` type to the mappings configuration to ensure URL aliases are captured.

```
mappings:
  -
    field: alias
    type: alias
```

# URL options

There are a number of options that can apply to the URL list.  These options are specified by the `url_options` array directive in the configuration:


| Option        | Explanation           |
| ------------- | ------------- |
| `include_query` | Will include the **query** part of the URL in the request.  If set to false, the crawler will only fetch the path component of the URL. |
| `include_fragment` | Will include the **fragment** part of the URL.  If set to false, the crawler will only fetch the path component of the URL. |
| `find_content_duplicates ` | Will check for **content** duplicates.  This will create a file called `url-content-duplicates.json` that contains a list of URLs that appear to resolve to the same content.  This is to avoid content duplication in the target system as well as provide a way to easily generate aliases. |
| `hash_selector` | This is an **XPath** selector that is used to generate the hash of content that is used to detect duplicates.  By default `sha1` is used as the hash algorithm and uses the `<body>` tag of the page as the determining content.|
| `hash_exclude_nodes ` | This is an array of **XPath** selectors to *exclude* when generating the hash to detect duplicates.  This could include elements that may appear on the page that might be metadata/cache busters or contain timestamps etc that can be safely excluded from building a hash for duplicate detection.  By default all `<script>`, `<!-- Comment -->`, `<style>`, `<input>` and `<head>` tags will be ignored.  |
| `urls` | This is an associative array of urls and their corresponding `include_query` and `include_fragment` settings (as above) to override the global setting, if required.|
	
 
**Example `url_options` configuration**:

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
```

**Example of overriding for specific URLs**:

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

# URLs in a separate file

You can also provide a list of URLs in a separate file. Your configuration can provide both `urls` and `urls_file` properties, or just one. Supply the `urls_file` as a relative path to the config file.

```
---
domain: http://www.example.com

urls:
 - /some/path
 - /some/path/subpath

urls_file: list_of_urls.yml
```

**Example of a separate URLs file**

Provide the list of urls in a separate file with a single `urls` property that contains the list of URLs. Example configuration of a `urls_file`:

```
---
urls:
  - /some/path
  - /some/other/path
```



# Mandatory element

Some elements may be considered mandatory for a row to be considered valid. For example; if a page does not contain a 'Title' then it may fail a mandatory requirement and be skipped.

This is controlled via the `mandatory` option against a field. For example:

```
mappings:
  -
    field: title
    selector: '#content-main h1'
    type: text
    options:
      mandatory: true
```



# Fetch options

URL content is retrieved via a Fetcher.  There are a number of options that can apply to how the content is retreived.  These options are specified by the `fetch_options` array directive in the configuration:

| Option        | Type | Default | Explanation           |
| ------------- | ---- | ------- | ------------- |
| `concurrency` | int | `10` | How many maximum concurrent requests should be used to fetch content.
| `delay` | int | `100` | Delay between requests in milliseconds.
| `cache_enabled` | boolean | `true` | If enabled, URL content is cached on disk for subsequent processing.  
| `cache_dir` | string | `/tmp/merlin_cache` | Directory to store the cache.  If the path does not exist it will be created.
| `fetcher_class` | string | `...FetcherSpatieCrawler` <br><br>*The full class path is / Fetcher / Fetchers / SpatieCrawler / FetcherSpatieCrawler*. | The full name-spaced class name of the Fetcher class to use to retrieve content.  In most normal circumstances this can be left alone.
| `execute_js` | boolean | false | Executes javascript on the page after fetching.  You need to ensure the necessary node dependencies are met and installed.  <br><br>**Note: JS is currently only available when using the default FetcherSpatieCrawler**.
| `follow_redirects` | boolean | `true` | If enabled, redirects e.g. `302` will be followed.
| `ignore_ssl_errors` | boolean | `false` | If enabled, will ignore SSL errors.
| `user_agent` | string | "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36" | Set the User-Agent to identify as in requests.
| `timeouts` | array | `connect_timeout: 15`<br>`timeout: 15`<br>`read_timeout: 30`<br> | Various fetching timeouts.  Note that if you use `execute_js` these timeouts will need to be at least doubled to allow time to run the javascript.

Most of the time the default Fetcher class should cover most usage requirements, however, you can specify a custom class name if you need to do something different.  Check `Merlin\Fetcher\Fetchers\*` for examples of how to implement.

**Example `fetcher_options` configuration**:
 
```
---
domain: http://www.exampple.com

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
  
  # Fetcher class, default FetcherSpatieCrawler
  # fetcher_class: '\Migrate\Fetcher\Fetchers\SpatieCrawler\FetcherSpatieCrawler'
  fetcher_class: '\Migrate\Fetcher\Fetchers\Curl\FetcherCurl'
  # fetcher_class: '\Migrate\Fetcher\Fetchers\RollingCurl\FetcherRollingCurl'
  
  # Execute on-load JS, default false.
  # Currently only available if using the FetcherSpatieCrawler fetcher class
  execute_js: false
  
  # Whether to follow redirects
  allow_redirects: true
  
  # Ignore SSL errors
  ignore_ssl_errors : true

  # Timeouts.  When using execute_js, you want to have reasonably long timeouts.
  # Not all timeouts are applicable to all Fetchers.
  timeouts:
    connect_timeout: 15, 
    timeout: 60,
    # FetcherSpatieCrawler only
    read_timeout: 30
    
```




# Basic text

Basic text fields can be mapped in the `mappings` section using the `text` type. Example configuration below:

```
mappings:
  -
    field: title
    selector: "#phbody_1_ctl01_h1Title"
    type: text
```

This type was used for the 'key messages' content. It supports both individual items, or arrays of items, e.g in the case of key messages there are multiple matches on the selector, so an array of plain-text results will exist in the JSON object for import.

```
mappings:
  -
    field: field_key_messages
    selector: .m-key-messages .m-b li
    type: text
    processors:
      convert_encoding:
        to_encoding: "HTML-ENTITIES"
        from_encoding: UTF-8
      html_entity_decode: { }
      whitespace: { }
```

This also includes additional processors, more detail on these can be found on the [Processors]() page.

# Long, formatted text

Long text is used for body content, or anywhere a rich-text WYSIWYG editor may be used. It also allows for embedded media (e.g documents, images).

This content will generally pass through multiple processors to ensure clean markup, and optionally allows for stripping undesirable attributes or tags.

The below example would capture an entire body of content found within the `#main` div, removing non-standard tags, removing empty tags, and stripping whitespace.

```
mappings:
  -
    field: field_paragraph_body
    selector: '//*[@id="main"]'
    type: long_text
    processors:
      - processor: remove_empty_tags
      -
        processor: convert_encoding
        to_encoding: HTML-ENTITIES
        from_encoding: UTF-8
      -
        processor: strip_tags
        allowed_tags: <h1><h2><h3><h4><h5><ul><ol><dl><dt><dd><li><p><a><strong><em><cite><blockquote><code><s><span><sup><sub><table><caption><tbody><thead><tfoot><th><td><tr><hr><pre><drupal-entity><br>
        remove_attr:
          - class
          - id
          - style
      - processor: whitespace
```
