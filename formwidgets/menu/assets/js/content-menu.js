/**
 * ContentMenu widget
 */

const contentMenu = {

    menuItemToggleAnimateInMs: 100,

    toggleMenu: function()
    {
        const me = this;

        $('.field-contentmenu').on('click', '.content-menu > .menu-item > .menu-header', function()
        {
            if ($(this).hasClass('active'))
            {
                $(this).removeClass('active')
                    .next()
                    .hide(me.menuItemToggleAnimateInMs);

                $(this).children('i.icon-angle-down')
                    .removeClass('icon-angle-down')
                    .addClass('icon-angle-right');

                return;
            }

            const $contentMenu = $(this).closest('.content-menu');

            $contentMenu.find('.menu-header')
                .removeClass('active');

            $contentMenu.find('.menu-header > i.icon-angle-down')
                .removeClass('icon-angle-down')
                .addClass('icon-angle-right');

            $contentMenu.find('.menu-body')
                .hide();

            $(this).addClass('active')
                .next()
                .show(me.menuItemToggleAnimateInMs);

            $(this).children('i.icon-angle-right')
                .removeClass('icon-angle-right')
                .addClass('icon-angle-down');
        });
    },

    events: function ()
    {
        this.toggleMenu();
    },

    init: function ()
    {
        this.events();
    }
};

$(document).ready(function(){
    contentMenu.init();
});