'use strict';

import ClipboardJS from "clipboard/dist/clipboard";

ready(function () {
    // Clipboard
    var clipboard = new ClipboardJS('.copy');
    clipboard.on('success', function (e) {
        setTimeout(function () {
            e.clearSelection();
        }, 150);
    });
});
