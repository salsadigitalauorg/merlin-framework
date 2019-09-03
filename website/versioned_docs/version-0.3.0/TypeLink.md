---
id: version-0.3.0-type-link
title: Link
sidebar_label: Link
original_id: type-link
---

Structured link representation.

> Note: Link is a combined output type, as a result processors need to be applied to the output rows.

## Options

- `link`*<string>*: Selector for the link attribute; DOM selectors will be relative to the `text` element.
- `text`*<string>*: The element that contains the text

## Usage

**DOM**
```
field: link
type: link
selector: .link-list li
options:
  link: href
  text: a
```

**Xpath**
```
field: link
type: link
selector: //*/[@class="link-list]/li
options:
  link: ./a/@href
  text: ./a
```

## Processors

```
field:
processors:
  text:
    - # <procesors for the text component>
  link:
    - # <processors for the link component>
```

## Output

```
{
  <field_name>: {
    "link": "internal:/path-to-resource",
    "text: "Link text"
  }
}
```
