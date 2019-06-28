---
id: type-paragraph
title: Paragraph
sidebar_label: Paragraph
---

Generate a structured type intended to build a structured representation of a Drupal paragraph.

> **Deprecated** This will be revisited to be made less Drupal-ised.

> Supports nested `Type` definitions.

## Options

Doesn't provide options.

## Paragraph type

A type to define the paragraph by.

## Usage

```
field: field_feature
type: paragraph
paragraph_type: container
selector: '.feature-modules-container'
children:
  -
    field: field_title
    type: text
    selector: 'h2'
  -
    field: field_description
    type: text
    selector: 'p'
```
