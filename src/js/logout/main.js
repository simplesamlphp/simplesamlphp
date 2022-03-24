'use strict';

import SimpleSAMLLogout from './logout.js';

document.addEventListener("DOMContentLoaded", function(event) {
    new SimpleSAMLLogout(document.body.id);
});
