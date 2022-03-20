import "es5-shim";
import "es6-shim";
import ClipboardJS from "clipboard/dist/clipboard";
import hljs from  "highlight.js/lib/core";
import xml from "highlight.js/lib/languages/xml";
import php from "highlight.js/lib/languages/php";
import json from "highlight.js/lib/languages/json";

window.readyHandlers = [];
window.ready = function ready(handler) {
    window.readyHandlers.push(handler);
    handleState();
};

window.handleState = function handleState () {
    if (document.readyState === 'interactive' || document.readyState === "complete") {
        while(window.readyHandlers.length > 0) {
            (window.readyHandlers.shift())();
        }
    }
};

document.onreadystatechange = window.handleState;

ready(function () {
    // Language selector
    var languageSelector = document.getElementById("language-selector");
    languageSelector.onchange = function() {
        let languageForm = document.getElementById("language-form");
        languageForm.submit();
        return true;
    };


    // Side menu
    var menuLink = document.getElementById("menuLink");
    menuLink.onclick = function(e) {
        e.preventDefault();

        let layout = document.getElementById("layout");
        if (layout.className.match(/(?:^|\s)active(?!\S)/)) {
            layout.className = layout.className.replace(/(?:^|\s)active(?!\S)/g , '');
        } else {
            layout.className += " active";
        }

        let foot = document.getElementById("foot");
        if (foot.className.match(/(?:^|\s)active(?!\S)/)) {
            foot.className = foot.className.replace(/(?:^|\s)active(?!\S)/g , '');
        } else {
            foot.className += " active";
        }

        if (menuLink.className.match(/(?:^|\s)active(?!\S)/)) {
            menuLink.className = menuLink.className.replace(/(?:^|\s)active(?!\S)/g , '');
        } else {
            menuLink.className += " active";
        }
    };


    // Expander boxes
    var expandable = document.querySelectorAll('.expandable > .expander');
    expandable.forEach(function (currentValue, index, arr) {
        currentValue.onclick = function(e) {
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
