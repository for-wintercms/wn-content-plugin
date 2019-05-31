# !!!  Attention: Alpha version  !!!

# Content Management Plugin

## Config files

Config files path: ```/themes/your-active-theme/content-items/*```


#### Partials

Directory: ```config-files-path/sections```

Regular partial files - [DOC partials](https://octobercms.com/docs/cms/partials)


#### Sections

Directory: ```config-files-path/sections```

Yaml files with section field settings.

Fields with settings:

 Field     | Importance   | Description
 --------- | ------------ | -------------
 label     | required     | Section title
 partial   | optional     | Partial file
 form      | required     | A configuration array, see [form fields](https://octobercms.com/docs/backend/forms#form-fields).

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

Directory: ```config-files-path/pages```

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
 form     | required     | A configuration array, see [form fields](https://octobercms.com/docs/backend/forms#form-fields).
 section  | optional     | Section file. If declared, the **form** settings field will be ignored.

##### Example:

```yaml
# config-files-path/pages/books.yaml
# ========================================

items:
    body_block:
        label: Books list
        section: 'book'     # config-files-path/sections/books.yaml
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
All events begin with a prefix ```wbry.content.*```