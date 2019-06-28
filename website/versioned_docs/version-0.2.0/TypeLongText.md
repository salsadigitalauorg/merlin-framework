---
id: version-0.2.0-type-long-text
title: Long Text
sidebar_label: Long Text
original_id: type-long-text
---

A block of long text that supports extracting raw HTML.

> **Hint**
> It is useful to use the [strip_tags](/docs/processor-strip-tags) with this type.

## Options

Doesn't provide options.

## Usage

**DOM**
```
field: long_text
type: long_text
selector: .content
```

**Xpath**
```
field: long_text
type: long_text
selector: //*/div[contains(@class, 'content')]
```
