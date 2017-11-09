
$(document).ready(function() {
    // get available languages
    var languages = $.map($('#language_selector option') ,function(option) {
        return option.text.toLowerCase();
    });

    $('#SelectLang').on("change", function (e) {
        if (-1 !== $.inArray(
                $('#language_selector-selectized').prev().text().toLowerCase(),
                languages
            )
        ) {
            e.currentTarget.submit();
        }
    });

});
