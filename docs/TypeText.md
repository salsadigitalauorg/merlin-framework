---
id: type-uuid
title: Uuid
sidebar_label: Uuid
---

Create a unique identifier from the selector. If no selector is provided a random UUID will be generated.

## Options

Doesn't provide options.

## Usage

**DOM**

```
field: uuid
type: uuid
selector: span.title
```


## Defaults

If element not found, you can specify a default value.  This can be a string, an array, or the evaluation of a function (at its simplest this could be used to return a primitive type like a bool).

**String Example**

```
field: uuid
type: uuid
selector: span.title
default: 'Default value'
```

**Array Example**

```
field: uuid
type: uuid
selector: span.title
default: 
  fields: 
    field_1: 'field_1_value'
    field_2: 'field_2_value'
```

**Function Example**

```
field: uuid
type: uuid
selector: span.title
default:
  function: |
    function($crawler) {
      $value = $crawler->getUri();
      return $value;
    }
```


