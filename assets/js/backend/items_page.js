var wd_items = wd_items || {

    select2Icons: function(selector)
    {
        var funSelectIcon = function(state)
        {
            if (!state.id)
                return state.text;
            return $('<span><i class="'+ state.id +'"></i>&nbsp;&nbsp;&nbsp;'+ state.text +'</span>');
        };
        $(selector).select2({
            templateResult: funSelectIcon,
            templateSelection: funSelectIcon
        });
    },

    select2CreateTmp: function(selector)
    {
        var funSelect = function(state)
        {
            if (!state.id)
                return state.text;
            return $('<span>'+ state.text +'&nbsp;&nbsp;<small class="br-a"><i>'+ state.id +'</i></small></span>');
        };
        $(selector).select2({
            templateResult: funSelect,
            templateSelection: funSelect
        });
    },

    addSubmenuControlPanels: function()
    {
        var panelOpen  = '<span class="page-control">',
            panelClose = '</span>';

        var btnEdit = ''+
            '<button class="btn btn-primary btn-xs" ' +
                    'data-btn-type="edit">' +
                '<i class="icon-pencil"></i>' +
            '</button>';

        var deleteConfirmMsg = (typeof this.deletePageConfirmMsg !== "undefined") ? this.deletePageConfirmMsg : '';
        var btnDelete = '' +
            '<button class="btn btn-primary btn-xs" ' +
                    'data-btn-type="delete" ' +
                    'data-request="onDeletePage" ' +
                    'data-request-data="" ' +
                    'data-request-confirm="'+ deleteConfirmMsg +'">' +
                '<i class="icon-trash-o"></i>' +
            '</button>';

        $('#layout-sidenav ul li[data-submenu-slug]').each(function(i, el)
        {
            var $this = $(this),
                panelBtns = '';

            if ($this.data('submenu-edit'))
                panelBtns += btnEdit;
            if ($this.data('submenu-delete'))
                panelBtns += btnDelete;

            $(el).append(panelOpen + panelBtns + panelClose);
        });
    },

    popupChangePage: function()
    {
        var me = this;
        $('#layout-sidenav ul li[data-btn-type="create_new_page"] a').click(function(){
            me.show_changePageModalData('create', $(this).closest('li'));
        });
        this.select2Icons('#popupChangePage select[name="icon"]');
    },

    events: function()
    {
        var me = this;

        // create items type
        $('#createItemPopup').on('click', '#popupCreateItemTabs ul li a', function(){
            $('#popupCreateItemTabs input[name="formType"]').val($(this).data('form-type'));
        });
        this.select2CreateTmp('#popupCreateItem select[name="readyTmp"]');

        // submenu control panel
        $('#layout-sidenav ul li').mouseover(function() {
            $(this).children('.page-control:eq(0)').show();
        }).mouseout(function() {
            $(this).children('.page-control:eq(0)').hide();
        });
        $('#layout-sidenav ul').on('mouseover mouseout click', 'li .page-control button', function(e)
        {
            var $this = $(this);
            switch (e.type)
            {
                case 'mouseover': $this.css({'opacity': '1'});   break;
                case 'mouseout':  $this.css({'opacity': '0.4'}); break;

                case 'click':
                {
                    var $parentLi = $this.closest('li');
                    switch ($this.data('btn-type'))
                    {
                        case 'edit':
                            me.show_changePageModalData('edit', $parentLi);
                            break;

                        case 'delete':
                            var submenuTitle = $parentLi.data('submenu-title'),
                                submenuSlug  = $parentLi.data('submenu-slug'),
                                confirmMsg   = $this.data('request-confirm').replace(/:page/, submenuTitle);

                            $this.data('request-data', "slug: '"+ submenuSlug +"'");
                            $this.data('request-confirm', confirmMsg);
                            break;
                    }
                }
            }
        });
    },

    show_changePageModalData: function(modalType, liObj)
    {
        var $modal  = $('#popupChangePage'),
            isEdit  = modalType === 'edit',
            iconVal = isEdit ? liObj.data('submenu-icon') : 'icon-plus',
            request = 'on'+ modalType.substring(0, 1).toUpperCase() + modalType.substring(1) +'Page',
            valData = {};

        valData.title    = isEdit ? liObj.data('submenu-title') : '';
        valData.slug     = isEdit ? liObj.data('submenu-slug')  : '';
        valData.order    = isEdit ? liObj.data('submenu-order') : '100';
        valData.old_slug = isEdit ? valData.slug : '';

        for (var k in valData)
            $modal.find('input[name="' + k + '"]').val(valData[k]);

        $modal.find('select[name="icon"] option[value="'+ iconVal +'"]:eq(0)').prop('selected', true).change();
        $modal.find('form[data-request]').data('request',request);
        $modal.find('.toggle-change').hide();
        $modal.find('.toggle-'+modalType).show();
        $modal.modal('show');
    },

    init: function()
    {
        this.addSubmenuControlPanels();
        this.popupChangePage();
        this.events();
    }
};

$(document).ready(function(){
    wd_items.init();
});