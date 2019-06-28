---
id: version-0.2.0-type-taxonomy-term
title: Taxonomy Term
sidebar_label: Taxonomy Term
original_id: type-taxonomy-term
---

Creates values from a regular expression in the selector.

> Creates a seperate output file.

> Supports nested `Type` definitions.

## Options

Doesn't provide options.

## Usage

```

field: field_page_type
type: taxonomy_term
vocab: category
selector: ".tags.links li"
children:
  -
    field: uuid
    type: uuid
    selector: ./@content
  -
    field: name
    type: text
    selector: ./@content
```
