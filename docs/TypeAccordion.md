---
id: type-accordion
title: Accordion
sidebar_label: Accordion
---

Define a representation for an accordion item.

> **Deprecated** This type is deprecated and will be refactored in a future release.

## Options

- `title`*<string>*: The DOM selector for the title element accordion.
- `body` *<string>*: The DOM selector for the body content element of the accordion.

## Usage

```
field: accordion
selector: "#accordion-container"
type: accordion
options:
  title: .accordion-title
  body: .accordion-content
```
