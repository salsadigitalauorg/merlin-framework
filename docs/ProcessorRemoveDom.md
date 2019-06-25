---
id: processor-remove-dom
title: Remove elements
sidebar_label: Remove elements
---

Removes specific parts of the DOM from the result.

## Options

- **selector**`<type: string>`: A string selector to remove from the DOM.
- **xpath**`<type: bool>`*optional*: If the selector is using xpath or not.

## Usage

```
processors:
  -
    processor: remove_dom
    selector: div
    xpath: false

```
