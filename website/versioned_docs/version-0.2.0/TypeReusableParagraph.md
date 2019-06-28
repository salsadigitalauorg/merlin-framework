---
id: version-0.2.0-type-reusable-paragraph
title: Reusable Paragraph
sidebar_label: Reusable Paragraph
original_id: type-reusable-paragraph
---

Generates a reusable paragraph structure. This will generate another file and return referenced a UUID so the content can be referenced in multiple places.

> Supports nested `Type` definitions.

## Options

- `type`*<string>*: The paragraph type to add to the output.
- `uuid`*<string>*: A matching field in the `children` that will be used as the UUID.

## Usage

```
name: call_to_action_video
type: reusable_paragraph
selector: div.content
options:
  type: paragraph_library
  uuid: uuid
children:
  -
    field: uuid
    type: uuid
    selector: ./div[@class="feat-img-container"]/img/@alt
  -
    field: field_paragraph_title
    type: text
    selector: ./div[@class='m-h']/h3
    options:
      allow_null: TRUE
```
