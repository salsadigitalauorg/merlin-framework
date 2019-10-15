---
id: type-menu-link
title: Menu Link
sidebar_label: Menu Link
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

## Custom parent selector

There is also support for overriding the menu parent based on an alternative selector.

This is useful for instances where a sub-menu structure exists that can be tied to a parent earlier in a menu hierachy (e.g via breadcrumbs or similar).

Below is a complex example of primary menu links first being discovered from a top nav menu bar, and subsequently being annotated on sub-pages via a sub-section menu found in a different region of the DOM, referencing a parent in a breadcrumb bar.

### Primary links (top nav)
```
---
domain: https://www.example.com

urls:
  - /

entity_type: menus
mappings:
  -
    field: main_menu
    name: main-menu
    type: menu_link
    selector: '//*[@id="top-nav"]/li'
    options:
      text: './a'
      link: './a/@href'
```

### Secondary, tertiary links (sidebar nav)

_Note:_ the use of the append option. This ensures the child menu links are appended to the same JSON file created by the above config.

```
---
domain: https://www.example.com

runner:
  append: true

urls:
  - /about
  - /publications-reports
  - /complaints
  - /public-statements

entity_type: menus
mappings:
  -
    field: main_menu
    name: main-menu
    type: menu_link
    selector: '//*[@id="region-sidebar-first"]//ul[@class="menu"]/li'
    options:
      text: './a'
      link: './a/@href'
      parent:
        selector: '//*[@class="breadcrumb"]/a[last()]'
        text: './text()'
        link: './@href'
    children:
      -
        type: menu_link
        selector: './ul/li'
        options:
          text: './a'
          link: './a/@href'
```
