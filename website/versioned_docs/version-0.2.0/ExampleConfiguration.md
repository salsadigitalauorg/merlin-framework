---
id: version-0.2.0-examples
title: Examples
original_id: examples
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
