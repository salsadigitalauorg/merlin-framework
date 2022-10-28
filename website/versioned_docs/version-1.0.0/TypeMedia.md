---
id: version-1.0.0-type-media
title: Media
sidebar_label: Media
original_id: type-media
---

Extract a structured representation of a media asset from the DOM.

> **Hint**
> It is useful to use the (strip_tags)[/docs/processor-strip-tags] with this type.

> Creates a seperate output file with link representations.

## Options

- `file` `<string>`: The selector to select the item, limited by *selector*.
- `name` `<string>`: The selector to select the name attribute, if using DOM selectors limited to the `file` element
- `alt` `<string>`: The selector select the alt attribute, if using the DOM selectors limited to the `file` element.
- `type` `<string>`: Media type label used to track the results of this category of media type.
- `process_name` `<string>`: Function callback that accepts `$value` (the name string) to allow full customisation of the output.
- `process_file` `<string>`: Function callback that accepts `$value` (the file string) to allow full customisation of the output.
- `full_details` `<bool>`: Includes the full details of generated media in the main results JSON as well as the separate media JSON that is generated.  Otherwise, only the media UUIDs are returned.
- `extra_attributes` `<array>`: Array of `field_name: selectors` to return any extra attributes that might be on the media item, e.g. `data` attributes.

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
  type: images
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
  type: images
  full_details: true
  extra_attributes:
	- data_tag: ./@data-tag
	- data_example: ./@data-example
```
