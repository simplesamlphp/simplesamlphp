<?php

/**
 * This file registers an autoloader for SimpleSAMLphp modules.
 *
 * @author Boy Baukema, SURFnet
 * @author Jaime Perez <jaime.perez@uninett.no>, UNINETT
 * @package SimpleSAMLphp
 */

spl_autoload_register(array('SimpleSAML_Module', 'autoloadPSR0'));
spl_autoload_register(array('SimpleSAML_Module', 'autoloadPSR4'));
