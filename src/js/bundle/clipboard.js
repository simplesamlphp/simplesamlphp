import ClipboardJS from "clipboard/dist/clipboard";

document.addEventListener("DOMContentLoaded", function(event) {
    var clipboard = new ClipboardJS('.copy');
    clipboard.on('success', function (e) {
        setTimeout(function () {
            e.clearSelection();
        }, 150);
    });
});
