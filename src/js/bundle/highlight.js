'use strict';

import * as poly from "@babel/polyfill/dist/polyfill";
import hljs from  "highlight.js/lib/core";
import xml from "highlight.js/lib/languages/xml";
import php from "highlight.js/lib/languages/php";
import json from "highlight.js/lib/languages/json";

ready(function () {
    // Syntax highlight
    hljs.registerLanguage('xml', xml);
    hljs.registerLanguage('php', php);
    hljs.registerLanguage('json', json);

    var codeBoxes = document.querySelectorAll('.code-box-content.xml, .code-box-content.php, .code-box-content.json');
    for (var i = 0; i < codeBoxes.length; i++) {
        hljs.highlightElement(codeBoxes[i]);
    };
});
