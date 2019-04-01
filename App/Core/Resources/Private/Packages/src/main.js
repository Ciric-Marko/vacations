$(document).ready(function () {
    // $('.tree.complete-tree').treeView();
    // $('.tree.ajax-tree').treeView({ajax: true});
    //
    // // // Alternative setup
    // // $('.tree.ajax-tree').treeView({
    // //     ajax: true,
    // //     setAjaxUrlCallback: function ($a) {
    // //         var id = $a.data('entry-id');
    // //         return "/Core/myTreeView/fetchAjaxTreeNode/" + id;
    // //     }
    // // });

    // $('.input-group.date').datetimepicker({
    //     icons: {
    //         time: "fa fa-clock-o",
    //         date: "fa fa-calendar",
    //         up: "fa fa-arrow-up",
    //         down: "fa fa-arrow-down",
    //         previous: "fa fa-arrow-left",
    //         next: "fa fa-arrow-right",
    //         today: 'fa fa-crosshairs',
    //         clear: 'fa fa-trash',
    //         close: 'fa fa-remove'
    //     },
    //     format: 'YYYY-MM-DDTHH:mm:ss.sssZ'
    // });

    $('.input-group.date input').each(function () {
        $(this).datetimepicker({
            icons: {
                time: "fa fa-clock-o",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-arrow-left",
                next: "fa fa-arrow-right",
                today: 'fa fa-crosshairs',
                clear: 'fa fa-trash',
                close: 'fa fa-remove'
            },
            format: 'YYYY-MM-DDTHH:mm:ssZ',
            useCurrent:'day'
            // format: 'YYYY-MM-DDTHH:mm:ss.sssZ'
        });
    });

});