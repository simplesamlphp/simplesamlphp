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
        var languageForm = document.getElementById("language-form");
        languageForm.submit();
        return true;
    };

    // Side menu
    var menuLink = document.getElementById("menuLink");
    menuLink.onclick = function(e) {
        e.preventDefault();

        var layout = document.getElementById("layout");
        if (layout.className.match(/(?:^|\s)active(?!\S)/)) {
            layout.className = layout.className.replace(/(?:^|\s)active(?!\S)/g , '');
        } else {
            layout.className += " active";
        }

        var foot = document.getElementById("foot");
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
});


$(document).ready(function () {
    // expander boxes
    $('.expandable > .expander').on('click', function (e) {
        e.preventDefault();
        let target = $(e.currentTarget);
        target.parents('.expandable').toggleClass('expanded');
        target.blur();
    });

    // syntax highlight
    hljs.registerLanguage('xml', xml);
    hljs.registerLanguage('php', php);
    hljs.registerLanguage('json', json);
    $('.code-box-content.xml, .code-box-content.php, .code-box-content.json').each(function (i, block) {
        hljs.highlightBlock(block)
    });

    // clipboard
    let clipboard = new ClipboardJS('.copy');
    clipboard.on('success', function (e) {
        setTimeout(function () {
            e.clearSelection();
        }, 150);
    });
});
