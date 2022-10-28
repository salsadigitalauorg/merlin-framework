---
id: version-1.0.0-type-alias
title: Alias
sidebar_label: Alias
original_id: type-alias
---

Returns the scrapers current path.

## Options

- `urldecode` `<bool>` Returns a URL decoded version of the alias.
- `return_uuid` `<bool>	` Returns a UUIDv3 generated from the alias.
- `truncate` `<int>` Truncate the alias to the given character length.
- `alias_map` `<string>` Path to a JSON file that maps original source alias to new destination alias.  The format required is an array of key:value pairs.

## Usage

```
field: alias
type: alias
```

## Output

```
{
  <field_name>: "/alias-of-the-path"
}
```
