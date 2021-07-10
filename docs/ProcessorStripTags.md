---
id: processor-strip-tags
title: Strip tags
sidebar_label: Strip tags
---

Removes tags and attributes from markup as defined.

## Options

- `allowed_tags` `<string>`: A string of allowed tags as formatted for [strip_tags](https://www.php.net/manual/en/function.strip-tags.php).
- `remove_attr` `<array>`: A list of attributes that should be removed.
- `allowed_classes` `<array>`: A list of classes that should be preserved.
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
    allowed_classes:
      - 'some-class'
      - 'another-class'
```
