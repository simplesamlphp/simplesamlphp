<?php

SimpleSAML_Logger::info('OpenID - Provider: Accessing OpenID Provider endpoint');

$server = sspmod_openidProvider_Server::getInstance();
$server->receiveRequest();
