var wd_items = wd_items || {

    events: function()
    {
        // create items type
        $('#createItemPopup').on('click', '#popupCreateItemTabs ul li a', function(){
            console.log('ok', $(this).data('form-type'));
            $('#popupCreateItemTabs input[name="formType"]').val($(this).data('form-type'));
        });
    },

    init: function()
    {
        this.events();
    }
};

$(document).ready(function(){
    wd_items.init();
});