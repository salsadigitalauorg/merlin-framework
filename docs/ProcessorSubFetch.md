---
id: processor-subfetch
title: Subfetch
sidebar_label: Subfetch
---

Subfetch facilitates fetching and processing either another content page or JSON (e.g. from an API request) based on a link that is being processed in the current configuration.  This is useful in cirumstances where a data structure is required that is a built from different pages or sources.

Subfetched content can be processed according to an inline configuration or a yaml config file.  It is also possible to nest subfetch requests in configurations.  All the processed results are saved as separate files and in the original caller output.

Note that subfetch requests do not form part of the main URL queue.  As such they are not processed in parallel so will add some processing time.  Content responses are cached (and will load from cache), JSON responses are not cached.


## Options

Subfetch inherits the values set in the main `fetch_options` configuration.  There are some additional options and overrides:

- `cache_enabled` `<bool>`: Override main cache option
- `headers` `<array>`: Headers to use in request (e.g. User-Agent)
- `config` `<array>`: An inline processing configuration
- `config_file` `<string>`: Path to yaml configuration file
- `json_data` `<bool>`: Process the fetched data as JSON, not HTML.

Note that `json_decode` is applied to the fetched data if `json_data` flag is true.  If the result cannot be decoded an error will be raised.


## Usage (Inline Configuration)

This example is processing a table that contains some items with a link to details for that item.  A `group` type is used to iterate over the table rows and get the details link for each item.  This link is then fed into `sub_fetch`, which visits the found link and processes it according to the inline configuration specified.

Example table:

| #           | Item        |  More Info  |
| ----------- | ----------- | ----------- |
| 1.  | Cake | <a>Cake Info Link</a> |
| 2.  | Pie | <a>Pie Info Link </a> |
| 3.  | Chips | <a>Chips Info Link</a> |
| n.  | ... | <a> Info Link</a> |

```
mappings:
  -
    field: title
    selector: //h1
    type: text
    processors:
      whitespace: {  }
  -
    field: items
    selector: //tr
    type: group
    each:
      -
        field: title
        selector: './td[2]'
        type: text
      -
        field: link
        selector: './td[3]/a/@href'
        type: text
        processors:
          sub_fetch:
            options:
              config:
                entity_type: item
                mappings:
                  -
                    field: title
                    selector: '//div[@class="title"]//span'
                    type: text
                  -
                    field: description
                    selector: '//div[@class="description"]//span'
                    type: text
                  -
                    field: price
                    selector: '//div[@class="price"]//span'
                    type: text

```



## Usage (File Configuration)

Use an external YAML configuration file instead of inline cofiguration:

```
mappings:
  -
    field: title
    selector: //h1
    type: text
    processors:
      whitespace: {  }
  -
    field: items
    selector: //tr
    type: group
    each:
      -
        field: title
        selector: './td[2]'
        type: text
      -
        field: link
        selector: './td[3]/a/@href'
        type: text
        processors:
          sub_fetch:
            options:
              config_file: /path/to/config.yml
                

```




