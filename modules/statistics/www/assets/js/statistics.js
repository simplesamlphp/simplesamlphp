$(document).ready(function () {
    $("#tabdiv").tabs();
    $('ul.tabset_tabs li').click(
        function () {
            $("html, body").animate({ scrollTop: 0 }, "slow");
        }
    )
});
