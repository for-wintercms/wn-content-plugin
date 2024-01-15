var translate_items = translate_items || {

    switchingTimeTranslatedFields: 200, // in milliseconds

    addFileLangMark: function()
    {
        var funcAddFileLangMark = function(i, el)
        {
            $formGroup = $(el).closest('.form-group');
            $markHtml = '<span class="file-lang-mark">'+$formGroup.data('field-name').toUpperCase()+'</span>';

            $formGroup.addClass('translate-field');

            if ($formGroup.children().prop('tagName').toLowerCase() === 'label')
                $formGroup.find('label:first-child').append($markHtml);
            else
            {
                var labelName = ($formGroup.parent().closest('.form-group').data('field-name')??'').toUpperCase();
                $formGroup.prepend('<label>'+labelName+' '+$markHtml+'</label>');
            }
        };

        // repeater widget
        $("#layout-body .form-group > .field-repeater").each(funcAddFileLangMark);

        // all other fields and widgets
        $("#layout-body .form-group [translate='1']").each(funcAddFileLangMark);
    },

    switchTranslatedFields: function(lang, switchTimeMs)
    {
        $("#layout-body .translate-field").each(function(i, el){
            if ($(el).data('field-name') === lang)
                $(el).show(switchTimeMs);
            else
                $(el).hide(switchTimeMs);
        });
    },

    events: function()
    {
        var me = this;

        // Switch translated fields
        me.switchTranslatedFields($('#translateTabs li.active:first').data('lang').toLowerCase(), 0);
        $('#translateTabs li').click(function(){
            me.switchTranslatedFields($(this).data('lang').toLowerCase(), me.switchingTimeTranslatedFields);
        });
    },

    init: function()
    {
        if ($('#layout-body #translateTabs').length <= 0)
            return;

        this.addFileLangMark();
        this.events();
    }
};

$(document).ready(function(){
    translate_items.init();
});