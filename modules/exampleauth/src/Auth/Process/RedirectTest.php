<?php

declare(strict_types=1);

namespace SimpleSAML\Module\exampleauth\Auth\Process;

use SimpleSAML\{Auth, Module, Utils};
use SimpleSAML\Assert\Assert;

/**
 * A simple processing filter for testing that redirection works as it should.
 *
 */
class RedirectTest extends Auth\ProcessingFilter
{
    /**
     * Initialize processing of the redirect test.
     *
     * @param array &$state  The state we should update.
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');

        // To check whether the state is saved correctly
        $state['Attributes']['RedirectTest1'] = ['OK'];

        // Save state and redirect
        $id = Auth\State::saveState($state, 'exampleauth:redirectfilter-test');
        $url = Module::getModuleURL('exampleauth/redirecttest');

        $httpUtils = new Utils\HTTP();
        $response = $httpUtils->redirectTrustedURL($url, ['StateId' => $id]);
        $response->send();
    }
}
