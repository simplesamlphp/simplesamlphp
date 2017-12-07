$(document).ready(function() {
    $('ul.tabset_tabs li').click(
        function() {
            var tab_id = $(this).attr('data-tab');
            $('ul.tabset_tabs li').removeClass('current');
            $('.tabset_content').removeClass('current');

            $(this).addClass('current');
            $("#"+tab_id).addClass('current');
            $("html, body").animate({ scrollTop: 0 }, "slow");
        }
    )
})
