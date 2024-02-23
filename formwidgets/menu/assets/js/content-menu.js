/**
 * ContentMenu widget
 */

const contentMenu = {

    menuItemToggleAnimateInMs: 100,

    events: function ()
    {
        const me = this;

        // Toggle menu
        $('.field-contentmenu').on('click', '.content-menu > .menu-item > .menu-header', function()
        {
            if ($(this).hasClass('active'))
            {
                $(this).removeClass('active').next().hide(me.menuItemToggleAnimateInMs);
                return;
            }

            const $contentMenu = $(this).closest('.content-menu');
            $contentMenu.find('.menu-header').removeClass('active');
            $contentMenu.find('.menu-body').hide();

            $(this).addClass('active').next().show(me.menuItemToggleAnimateInMs);
        });
    },

    init: function ()
    {
        this.events();
    }
};

$(document).ready(function(){
    contentMenu.init();
});