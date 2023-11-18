var translate_items = translate_items || {

    translateItems: function()
    {
        $("#layout-body .form-group [translate='1']").each(function(i, el)
        {
            var isTranslateFieldType = false;
            var tagType = $(el).prop('tagName').toLowerCase();

            switch (tagType)
            {
                case 'div':
                    if ($(el).attr('data-control') === 'richeditor')
                        tagType = 'richeditor';
                    //console.log($('[data-control="richeditor"]:eq(0)').find('div.fr-element:eq(0)').html('Новый контент...'));
                    isTranslateFieldType = true;
                    break;
                case 'textarea':
                    isTranslateFieldType = true;
                    break;
                case 'input':
                {
                    var fieldType = $(el).attr('type');
                    if (typeof fieldType == "undefined")
                        break;
                    switch (fieldType.toLowerCase()) {
                        case 'file':
                        case 'image':
                        case 'button':
                        case 'submit':
                        case 'reset':
                        case 'radio':
                        case 'checkbox':
                            break;
                        default:
                            isTranslateFieldType = true;
                    }
                    break;
                }
            }

            var fieldName = $(el).attr('name');

            if (! isTranslateFieldType || typeof fieldName == "undefined")
            {
                $(el).removeAttr('translate');
                return;
            }

            fieldName = fieldName.trim();
            var re = /^Item\[items\]\[(.*?)\]$/;

            if (! fieldName.match(re))
            {
                $(el).removeAttr('translate');
                return;
            }

            fieldName = fieldName.replace(re, "$1").trim();

            console.log(fieldName);
        });

        // search all translate fields
        // var transFields = {};
        // $('#layout-body form.layout').find("[name^='Item[items][']").each(function(i, el)
        // {
        //     var tagName = $(el).prop('tagName').toLowerCase();
        //     switch (tagName)
        //     {
        //         case 'textarea': break;
        //         case 'input':
        //         {
        //             var fieldType = $(el).attr('type').toLowerCase();
        //             switch (fieldType) {
        //                 case 'file':
        //                 case 'image':
        //                 case 'button':
        //                 case 'submit':
        //                 case 'reset':
        //                 case 'radio':
        //                 case 'checkbox':
        //                     return;
        //             }
        //             break;
        //         }
        //         default: return;
        //     }
        //
        //     var fieldName = $(el).attr('name').trim();
        //     var re = /^Item\[items\]\[(.*?)\]$/;
        //     if (! fieldName.match(re))
        //         return;
        //     fieldName = fieldName.replace(re, "$1").trim();
        //     if (! fieldName)
        //         return;
        //
        //     transFields[fieldName] = {'item': $(el)};
        // });

        // console.log(transFields);

        // $.request('onGetTranslateItems', {
        //     success: function(data) {
        //         if (typeof data.result !== "object" || data.result.length <= 0)
        //             return;
        //         console.log(data.result);
        //     }
        // })
    },

    events: function()
    {
        var me = this;

        console.log('new translate item JS');

        // ajax before send
        $(window).on('ajaxBeforeSend', function(event, context) {
            //---
            console.log(context);
            //---
        });

        $('#translateTabs li').click(function(){
            //
        });

        // Fill in blanks and Save
        /*
        $('#fill_and_save').click(function()
        {
            var langLocales = [];
            var $langLocales = $('.langLocale');
            if (! $langLocales.length)
                return;
            $langLocales.each(function(i, el) {
                langLocales[i] = $(el).data('field-name').toLowerCase();
            });

            var funcFieldsFill = function(el, tagName)
            {
                // field name attr
                var fieldName = $(el).attr('name').trim();//
                var re = /^Item\[items\]\[(.*?)\]$/;
                if (! fieldName.match(re))
                    return;
                fieldName = fieldName.replace(re, "$1").trim();
                if (! fieldName)
                    return;

                // fill fields
                var $transField;
                var $emptyFields = [];
                var fieldSetData = $(el).val().trim();
                if (! fieldSetData)
                    $emptyFields[0] = $(el);

                var funcSetField = function($el)
                {
                    $el.val(fieldSetData);
                    if (tagName === 'textarea') {
                        if ($el.attr('id').match(/^RichEditor/))
                            $el.parent().find("div.fr-element").html(fieldSetData);
                    }
                };

                langLocales.forEach(function(langCode) {
                    $transField = $("[name='Item["+langCode+"]["+fieldName+"]']");
                    if ($transField.length !== 1)
                        return;
                    if (fieldSetData) {
                        if (! $transField.val().trim())
                            funcSetField($transField);
                    }
                    else {
                        fieldSetData = $transField.val().trim();
                        if (! fieldSetData)
                            $emptyFields[$emptyFields.length] = $transField;
                    }
                });

                if (fieldSetData) {
                    $emptyFields.forEach(function($el) {
                        funcSetField($el);
                    });
                }
            };

            // return false;
        });
        */
    },

    init: function()
    {
        if ($('#layout-body #translateTabs').length <= 0)
            return;

        this.translateItems();
        this.events();
    }
};

$(document).ready(function(){
    translate_items.init();
});