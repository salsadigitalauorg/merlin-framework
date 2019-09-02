---
id: url-options
title: URL Options
---

There are a number of options that can apply to the URL list.  These options are specified by the `url_options` array directive in the configuration:


| Option        | Explanation           |
| ------------- | ------------- |
| `include_query` | Will include the **query** part of the URL in the request.  If set to false, the crawler will only fetch the path component of the URL. |
| `include_fragment` | Will include the **fragment** part of the URL.  If set to false, the crawler will only fetch the path component of the URL. |
| `find_content_duplicates ` | Will check for **content** duplicates.  This will create a file called `url-content-duplicates.json` that contains a list of URLs that appear to resolve to the same content.  This is to avoid content duplication in the target system as well as provide a way to easily generate aliases. |
| `hash_selector` | This is an **XPath** selector that is used to generate the hash of content that is used to detect duplicates.  By default `sha1` is used as the hash algorithm and uses the `<body>` tag of the page as the determining content.|
| `hash_exclude_nodes ` | This is an array of **XPath** selectors to *exclude* when generating the hash to detect duplicates.  This could include elements that may appear on the page that might be metadata/cache busters or contain timestamps etc that can be safely excluded from building a hash for duplicate detection.  By default all `<script>`, `<!-- Comment -->`, `<style>`, `<input>` and `<head>` tags will be ignored.  |
| `urls` | This is an associative array of urls and their corresponding `include_query` and `include_fragment` settings (as above) to override the global setting, if required.|
	
 
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
```

## Example of overriding for specific URLs

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

## URLs in a separate file

You can also provide a list of URLs in a separate file. Your configuration can provide both `urls` and `urls_file` properties, or just one. Supply the `urls_file` as a relative path to the config file.

```
---
domain: http://www.example.com

urls:
 - /some/path
 - /some/path/subpath

urls_file: list_of_urls.yml
```

## Example of a separate URLs file

Provide the list of urls in a separate file with a single `urls` property that contains the list of URLs. Example content of a `urls_file`:

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