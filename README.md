# Attention: Pre-Alpha version

#### Repeater config files path

```
~/themes/your_theme/repeaters/config-repeatername.yaml
```

#### Example config yaml

```yaml
# ===================================
#  Form Content Control Config
# ===================================

menu:
  label: 'Home page'   #required
  slug: index          #required
  icon: icon-home
  order: 2


### Docs
### https://octobercms.com/docs/backend/forms#field-types
### https://octobercms.com/docs/backend/forms#form-field-options
### https://octobercms.com/docs/backend/forms#widget-repeater

repeater:
  index-list:
    label: 'Index list'   #required
    fields:               #required and not empty
      jobs-list:
        label: 'Jobs list'
        prompt: 'wbry.content::content.prompt'
        span: auto
        type: repeater
        form:
          fields:
            test1:
              label: 'Sub jobs list'
              prompt: 'wbry.content::content.prompt'
              span: auto
              type: repeater
              form: {}
      super-jobs-list:
        label: 'Super jobs list'
        prompt: 'Individual name'
        span: auto
        type: repeater
        form: {}
      text1:
        label: Text
        span: auto
        type: text
  products:
    label: 'Product list'
    fields:
      product-type:
        label: 'Product types'
        prompt: 'wbry.content::content.prompt'
        span: auto
        type: repeater
        form: {}
```
