/**
 * Set focus to the element with the given id.
 *
 * @param id  The id of the element which should receive focus.
 */
function SimpleSAML_focus(id)
{
    element = document.getElementById(id);
    if (element != null) {
        element.focus();
    }
}


/**
 * Show the given DOM element.
 *
 * @param id  The id of the element which should be shown.
 */
function SimpleSAML_show(id)
{
    element = document.getElementById(id);
    if (element == null) {
        return;
    }

    element.style.display = 'block';
}


/**
 * Hide the given DOM element.
 *
 * @param id  The id of the element which should be hidden.
 */
function SimpleSAML_hide(id)
{
    element = document.getElementById(id);
    if (element == null) {
        return;
    }

    element.style.display = 'none';
}

// Attach the `fileselect` event to all file inputs on the page
$(document).on('change', ':file', function () {
    var input = $(this),
        numFiles = input.get(0).files ? input.get(0).files.length : 1,
        label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
    input.trigger('fileselect', [numFiles, label]);
});

$(document).ready(function () {
    $('.language-menu').selectize();
    $('#organization').selectize();
    new ClipboardJS('.clipboard-btn');

// Watch for custom `fileselect` event
    $(':file').on('fileselect', function (event, numFiles, label) {

        var input = $(this).parents('.pure-button-group').find(':text'),
            log = numFiles > 1 ? numFiles + ' files selected' : label;

        if (input.length) {
            input.val(log);
        } else {
            if (log) {
                document.getElementById('show-file').innerHTML = log;
            }
        }
    });

});

