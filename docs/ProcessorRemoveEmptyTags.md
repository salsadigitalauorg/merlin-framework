---
id: processor-remove-empty-tags
title: Remove Empty Tags
sidebar_label: Remove empty tags
---

Strips any empty tags from the result (e.g `<div> </div>`)

## Options

Doesn't provide options.

## Usage

```
processors:
  -
    processor: remove_empty_tags
```

## Known issues

- Currently this doesn't allow valid empty tags so it can cause issues with certain markup strings that are expected to container empty values (eg. a table with empty cells)
