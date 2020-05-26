---
id: processor-strip-tags
title: Strip tags
sidebar_label: Strip tags
---

Removes tags and attributes from markup as defined.

## Options

- **allowed_tags**`<type: string>`: A string of allowed tags as formatted for [strip_tags](https://www.php.net/manual/en/function.strip-tags.php).
- **remove_attr**`<type: array>`: A list of attributes that should be removed.

## Usage

```
processors:
  -
    processor: strip_tags
    allowed_tags: <h1><h2><h3>
    remove_attr:
      - id
      - class
      - style
```
