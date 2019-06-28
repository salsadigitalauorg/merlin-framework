---
id: version-0.2.0-type-link
title: Link
sidebar_label: Link
original_id: type-link
---

Structure link representation.

## Options

- `link`*<string>*: Selector for the link attribute; DOM selectors will be relative to the `text` element.
- `text`*<string>*: The element that contains the text

## Usage

**DOM**
```
field: link
type: link
selector: .link-list li
options:
  link: href
  text: a
```

**Xpath**
```
field: link
type: link
selector: //*/[@class="link-list]/li
options:
  link: ./a/@href
  text: ./a
```
