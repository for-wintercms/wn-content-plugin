const translate_items = {

    switchingTimeTranslatedFields: 200, // in milliseconds

    addFileLangMark: function()
    {
        const funcAddFileLangMark = function(i, el)
        {
            $formGroup = $(el).closest('.form-group');

            if ($formGroup.hasClass('translate-field'))
                return;
            else
                $formGroup.addClass('translate-field');

            $markHtml = '<span class="file-lang-mark">'+$formGroup.data('field-name').toUpperCase()+'</span>';

            const $firstLabel = $formGroup.children('label:first-child');
            if ($firstLabel.length && $firstLabel.prop('tagName').toLowerCase() === 'label')
                $firstLabel.append($markHtml);
            else
            {
                const formGroupId = $formGroup.attr('id');
                if (formGroupId)
                {
                    const $findLabel = $formGroup.find('label[for="'+formGroupId.replace(/-group$/,'')+'"]:first-child');
                    if ($findLabel.length)
                    {
                        $findLabel.append($markHtml);
                        return;
                    }
                }

                const labelName = ($formGroup.parent().closest('.form-group').data('field-name')??'').toUpperCase();
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
        const me = this;

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