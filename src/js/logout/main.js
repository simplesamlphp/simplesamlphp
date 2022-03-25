'use strict';

import * as ready from '../bundle/ready';
import SimpleSAMLLogout from './logout';

ready(function () {
    new SimpleSAMLLogout(document.body.id);
});
