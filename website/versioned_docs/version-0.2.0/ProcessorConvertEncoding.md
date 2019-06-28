---
id: version-0.2.0-processor-convert-encoding
title: Convert Encoding
sidebar_label: Convert Encoding
original_id: processor-convert-encoding
---

Converts character encoding of data from one type to another. This uses `mb_convert_encoding` and should allow the same values.

- [phpdocs](https://www.php.net/manual/en/function.mb-convert-encoding.php)

## Options

- **to_encoding**`<default: UTF-8>`: The encoding to convert to.
- **from_encoding**`<default: null>`: The encoding to convert form.

## Usage

```
processors:
  -
    processor: convert_encoding
    to_encoding: UTF-8
    from_encoding: auto
```
