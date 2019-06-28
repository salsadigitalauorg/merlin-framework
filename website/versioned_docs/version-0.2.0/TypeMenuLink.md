---
id: version-0.2.0-type-menu-link
title: Menu Link
sidebar_label: Menu Link
original_id: type-menu-link
---

Define an iterative menu strructure.

> Supports nested `Type` definitions.

## Options

- `link` *<string>*: The DOM selector for the link attribute.
- `text` *<string>*: The DOM selector for link text.

## Usage

```

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
