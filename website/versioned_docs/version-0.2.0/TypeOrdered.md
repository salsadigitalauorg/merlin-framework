---
id: version-0.2.0-type-ordered
title: Ordered
sidebar_label: Ordered
original_id: type-ordered
---

Find structured children elements within a parent container.

> Supports nested `Type` definitions.

> Children will be restricted to the parent selector.

## Options

Doesn't provide options.

## Available items

This type supports the `available_items` property. This allows your to define basic matches to pass a list of children over.

If there is only one item in the array it will be used for all matches.

## Usage

```
field: field_secondary_links
type: ordered
selector: '//*/ul/li'
available_items:
  -
    by:
      attr: class
      text: content
    fields:
      -
        field: related_link_group
        type: paragraph
        paragraph_type: related_link_group
        children:
          -
            field: field_related_link
            type: link
          -
            field: field_related_link_blurb
            selector: p
            type: text
```
