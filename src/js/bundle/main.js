'use strict';

import "es5-shim";
import "es6-shim";
import * as ready from './ready';
import * as language from './language';
import * as expander from './expander';
import * as clipboard from './clipboard';
import * as highlight from './highlight';

if (window.innerHeight < 600) {
    document.getElementById('content').scrollIntoView(true)
}
