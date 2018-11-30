import "es6-shim";
import "clipboard/dist/clipboard";
import "selectize/dist/js/selectize";

$(document).ready(function () {
    // get available languages
    let languages = $.map($('#language-selector option'), function (option) {
       return option.text.toLowerCase();
    });

    // initialize selectize
    $('#language-selector').selectize({
        onChange: function () {
            if (-1 !== $.inArray($('#language-selector-selectized').prev().text().toLowerCase(), languages)) {
                $('#language-form').submit();
            }
        },
    });

    // side menu
    $('#menuLink').click(function (e) {
        e.preventDefault();
        $('#layout').toggleClass('active');
        $('#foot').toggleClass('active');
        $(this).toggleClass('active');
    });
});