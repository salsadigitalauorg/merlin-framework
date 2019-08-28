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
