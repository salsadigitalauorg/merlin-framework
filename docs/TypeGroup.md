---
id: type-group
title: Group
sidebar_label: Group
---

The group type creates a nested structure that is created from elements within a parent container.

- Supports nested `Type` definitions.
- Supports nested `Group` types.

**Notes:** 

 1. Nodes in the group have no knowledge of their ancestors.
 2. You need to specify a leading `.` e.g. `.//div` for XPath selectors to ensure children nodes of the main selector are used.  Using a root selector like `//div` may result in unexpected output.


## Building the Group

The group type is intended to be used with a selector that returns multiple elements (e.g. a class name) that contain other child elements that will be turned into a nested data structure.

The group type requires the `each` property.  This is an array of fields that will build up each item in the group.



## Example Usage

```
mappings:
  -
    field: contacts
    type: group
    selector: '//*[@id="main"]//div[contains(@class, "m-contacts")]'
    
    options:
       # Results can be sorted by a particular field (or not at all):
    	sort_field: "field_title"              
    	sort_direction: "desc"
    	
    	# Skip entire group if any field is required
    	required_skip_group: false
    	
    	# Skip child of group if any field is required
    	required_skip_child: false
    	
    	# Add a 'uuid' field to each child of group
    	generate_uuid: false
    
    each:
      -
        field: field_title
        type: text
        selector: './/h4'
        options:
          required: true
      -
        field: field_department_name
        selector: './/em'
        type: text
        processors:
          whitespace: { }
      -
        field: field_summary
        selector: './/p'
        type: text
        processors:
          whitespace: { }
      -
        field: field_email
        selector: './/a[@class="email"]'
        type: text
        processors:
          whitespace: { }
          replace:
            pattern: "Email:"
            replace: ""
      -
        field: field_postal_address 
        selector: .location
        type: text
        processors:
          whitespace: { }
          replace:
            pattern: "Location:"
            replace: ""            
```


This config would give output similar to:

```
[
  {
    "alias": "\/page-with-some/contacts\/",
    "title": "Useful Contacts",
    "contacts": {
      "type": "group",
      "children": [
        {
          "field_title": "Unicorn Rearing Information",
          "field_department_name": "Department for Unicorns",
          "field_summary": "Information about the work we do with unicorns.",
          "field_email": "unicorns.exist@example.com",
          "field_postal_address": "1234 Some St, Melbourne, Australia",
          "uuid": "3ab16fca-87d3-3300-a132-00000000000a"
        },
        {
          "field_title": "Rainbow Painting Information",
          "field_department_name": null,
          "field_summary": "For information about rainbows and available colours.",
          "field_email": "rainbow.painting@example.com",
          "field_postal_address": null,
          "uuid": "3ab16fca-87d3-3300-a132-00000000000b"
         },
         ,
        {
          "field_title": "Goose Safety Information",
          "field_department_name": "Department for Goose Safety",
          "field_summary": "For information about staying safe as a Goose abroad.",
          "field_email": "goose.abroad@example.com",
          "field_postal_address": null,
          "uuid": "3ab16fca-87d3-3300-a132-00000000000c"
         }
       ]
     }
  }
]
```
