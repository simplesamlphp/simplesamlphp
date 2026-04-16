<?php

declare(strict_types=1);

namespace SimpleSAML\Controller;

use SimpleSAML\Configuration;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;
use Symfony\Component\HttpFoundation\Response;

final class FrontpageController
{
    public function redirect(): Response
    {
        $config = Configuration::getInstance();
        $httpUtils = new HTTP();
        $redirect = $config->getOptionalString('frontpage.redirect', Module::getModuleURL('core/welcome'));

        return new RunnableResponse([$httpUtils, 'redirectTrustedURL'], [$redirect]);
    }
}
