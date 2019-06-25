---
id: type-long-text
title: Long Text
sidebar_label: Long Text
---

Extract a structured representation of a media asset from the DOM.

> **Hint**
> It is useful to use the (strip_tags)[/docs/processor-strip-tags] with this type.

> Creates a seperate output file with link representations.

## Options

- `file`*<string>*: The selector to select the item, limited by *selector*.
- `name`*<string>*: The selector to select the name attribute, if using DOM selectors limited to the `file` element
- `alt`*<string>*: The selector select the alt attribute, if using the DOM selectors limited to the `file` element.
- `process_name`*<string>*: Function callback that accepts `$value` (the name string) to allow full customisation of the output.
- `process_file`*<string>*: Function callback that accepts `$value` (the file string) to allow full customisation of the output.

## Usage

**DOM**
```
field: field_featured_image
type: media
selector: .feat-img-container
options:
  file: img
  name: data-name
  alt: alt
  processor_file: |
    function ($value) {
      return "migrated-$value";
    }
  process_name: |
    function ($value) {
      return "migrated-$value";
    }
```

**Xpath**
```
field: field_featured_image
type: media
selector: "./div[@class='feat-img-container']"
options:
  file: ./img/@src
  name: /img/@data-name
  alt: ./img/@alt
  processor_file: |
    function ($value) {
      return "migrated-$value";
    }
  process_name: |
    function ($value) {
      return "migrated-$value";
    }
```
