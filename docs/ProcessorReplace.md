---
id: processor-replace
title: Replace
sidebar_label: Replace
---

Regex search/replace.

## Options

- **pattern**`<type: string>`: A regex string match and replace
- **replace**`<type: string>`*optional*: The text to replace with, if omitted matches will be removed.

## Usage

*Remove numbers*
```
processors:
  -
    processor: replace
    pattern: \d+
    replace: false

```
