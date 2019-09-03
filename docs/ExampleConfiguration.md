---
id: examples
title: Examples
---

A configuration file should be made up of several key components.
- Base domain (e.g `https://www.example.com`)
- URL list (e.g an array of paths off the parent domain)
- Optional URL configuration
- Entity type (unique name for this content structure)
- Mapping configuration (selectors, data processing)

See the example files in the [examples](https://github.com/salsadigitalauorg/merlin-framework/tree/develop/examples) folder for complete examples.

## Basic text

Basic text fields can be mapped in the `mappings` section using the `text` type.

For example this may commonly be used for page content (selector `h1` with id `title`):

```
mappings:
  -
    field: title
    selector: "h1#title"
    type: text
```
You can also use xpath selectors:

```
mappings:
  -
    field: title
    selector: "//h1[@id='title']"
    type: text
```

Another example could be pulling content items from an unordered list and passing them through some data processors:
```
mappings:
  -
    field: field_key_messages
    selector: ul.key-messages li
    type: text
    processors:
      convert_encoding:
        to_encoding: "HTML-ENTITIES"
        from_encoding: UTF-8
      html_entity_decode: { }
      whitespace: { }
```


## Long, formatted text

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


## Example menu

Menu structures use the `menu_link` type. This sample configuration demonstrates how to pull a main menu with parent/child relationships in-tact.

The selector uses an Xpath to reference the element in the DOM, in this case all list-items contained in the header nav are evaluated for top level links. The `text` and `link` options are sub-selectors to help define where link text and link values should come from.

The `children` section allows for sub-menu items to be defined via their own `selector` and configuration.

```
---
domain: https://www.example.com

urls:
  - /

entity_type: menus

mappings:
  -
    field: main_menu
    name: main_menu
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


## URL aliases

The URL alias of each content should be preserved so URLs can remain in-tact when migrated into the destination CMS. Simply attach the `alias` type to the mappings configuration to ensure URL aliases are captured.

```
mappings:
  -
    field: alias
    type: alias
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
