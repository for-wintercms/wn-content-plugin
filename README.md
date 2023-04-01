# Content Management Plugin

## Working integrations

 - *(optional)* For a complete translation system, install the [**Winter.Translate**](https://github.com/wintercms/wn-translate-plugin) plugin.

## Config files

Config files path: ```/themes/your-active-theme/content-items/*```


#### Partials

Directory: ```content-items/sections```

Regular partial files - [DOC partials](https://wintercms.com/docs/cms/partials)


#### Sections

Directory: ```content-items/sections```

Yaml files with section field settings.

Fields with settings:

 Field     | Importance   | Description
 --------- | ------------ | -------------
 label     | required     | Section title
 partial   | optional     | Partial file
 form      | required     | A configuration array, see [form fields](https://wintercms.com/docs/backend/forms#form-fields).

##### Example:

```yaml
# config-files-path/sections/books.yaml
# ========================================

label: Section title
partial: 'partial-file'
form:
    fields:
        title:
            label: Title
            type: text
            span: left
        books:
            label: Books
            type: dropdown
            span: right
            options:
                book1: Book - 1
                book2: Book - 2
# etc other configs
```


#### Pages

Directory: ```content-items/pages```

Yaml files with page margin settings.

##### Fields with settings:

 Field    | Importance   | Description
 -------- | ------------ | -------------
 items    | required     | Page fields and sections. See more below.

##### *[items]* page fields and sections:

Declare a key-value pair. Value settings:

 Field    | Importance   | Description
 -------- | ------------ | -------------
 label    | required     | Page title (for backend)
 form     | required     | A configuration array, see [form fields](https://wintercms.com/docs/backend/forms#form-fields).
 section  | optional     | Section file. If declared, the **form** settings field will be ignored.

##### Example:

```yaml
# content-items/pages/books.yaml
# ========================================

items:
    body_block:
        label: Books list
        section: 'book'     # content-items/sections/books.yaml
    location:
        label: Location
        form:
            fields:
                desc:
                    label: Description
                    type: text
                    span: left
                list:
                    label: Choose the country
                    type: dropdown
                    span: right
                    options:
                        ru: Russia
                        en: America
# etc other configs
```


## Events

The plugin has various events for extend the logic.
All events begin with a prefix ```forwintercms.content.*```


## Components

#### "GetContent" component

```
Example
...

[getContent]
==
{% component 'getContent' %}

...
```

#### "GetItems" component

```
Example
...

[getItems]
==

get fields
{% set pageHeaderData = getItems.item('page-slug', 'header') %}

or for multiple items fields
{% set pageAllData = getItems.items('page-slug') %}
{% set pageBlockData = getItems.items('page-slug', ['header', 'menu']) %}

or ready content
{{ getItems.item('page-slug', 'header', true) }}

or multiple ready content
{{ getItems.items('page-slug', ['header', 'menu'], true) }}

...
```