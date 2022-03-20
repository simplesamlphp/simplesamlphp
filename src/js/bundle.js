import ClipboardJS from "clipboard/dist/clipboard";
import hljs from  "highlight.js/lib/core";
import xml from "highlight.js/lib/languages/xml";
import php from "highlight.js/lib/languages/php";
import json from "highlight.js/lib/languages/json";

// Expander boxes
var expandable = document.querySelectorAll('.expandable > .expander');
expandable.forEach(function (currentValue, index, arr) {
    currentValue.onclick = function (e) {
        e.preventDefault();

        var parent = e.currentTarget.parentNode;
        if (parent.className.match(/(?:^|\s)expanded(?!\S)/)) {
            parent.className = parent.className.replace(/(?:^|\s)expanded(?!\S)/g , '');
        } else {
            parent.className += " expanded";
        }

        e.currentTarget.blur();
    }
});

ready(function () {
    // Syntax highlight
    hljs.registerLanguage('xml', xml);
    hljs.registerLanguage('php', php);
    hljs.registerLanguage('json', json);

    var codeBoxes = document.querySelectorAll('.code-box-content.xml, .code-box-content.php, .code-box-content.json');
    codeBoxes.forEach(function (currentValue, index, arr) {
        hljs.highlightElement(currentValue);
    });


    // Clipboard
    let clipboard = new ClipboardJS('.copy');
    clipboard.on('success', function (e) {
        setTimeout(function () {
            e.clearSelection();
        }, 150);
    });
});
