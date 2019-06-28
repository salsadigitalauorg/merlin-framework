---
id: type-taxonomy-filter
title: Taxonomy Filter
sidebar_label: Taxonomy Filter
---

Creates values from a regular expression in the selector.

> **Deprecated** This will be revisited to be made less Drupal-ised.

> Creates a seperate output file.

## Options

- `pattern`*<string>*: A regular expression to match in the selector.
- `vocab`*<string>*: A name for the output file.

## Usage

```
field: taxonomy_filter
selector: ".tags.links li"
type: taxonomy_filter
options:
  pattern: "/[A-z]+/"
  vocab: category
```
