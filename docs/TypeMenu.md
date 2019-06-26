---
id: type-menu
title: Menu
sidebar_label: Menu
---

This will attempt to look through the configured selector a attempt to locate an "a" tag. From the "a" tag the href and link text will be inherited.

> **Deprecated** This type is deprecated and will be refactored in a future release. See [Menu Link](/docs/type-menu-link) for future support.

> Creates a seperate output file with link representations.

## Options

- `children`*<string>*: The DOM selector for child links.
- `link` *<string>*: The DOM selector for the link attribute.
- `text` *<string>*: The DOM selector for link text.
- `remove_duplicates` *<boolean>*: If we should attempt to remove duplicate links.

## Usage

```
field: main_menu
name: main-menu
type: menu
selector: ".header-nav .navbar ul > li"
options:
  children: ".dropdown li"
  link: href
  text: h3
  remove_duplicates: true
```
