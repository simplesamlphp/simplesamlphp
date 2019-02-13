<?php

namespace SimpleSAML\Module\exampleautth\Auth\Process;

/**
 * A simple processing filter for testing that redirection works as it should.
 *
 */

class RedirectTest extends \SimpleSAML\Auth\ProcessingFilter
{
    /**
     * Initialize processing of the redirect test.
     *
     * @param array &$state  The state we should update.
     */
    public function process(&$state)
    {
        assert(is_array($state));
        assert(array_key_exists('Attributes', $state));

        // To check whether the state is saved correctly
        $state['Attributes']['RedirectTest1'] = ['OK'];

        // Save state and redirect
        $id = \SimpleSAML\Auth\State::saveState($state, 'exampleauth:redirectfilter-test');
        $url = \SimpleSAML\Module::getModuleURL('exampleauth/redirecttest.php');
        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url, ['StateId' => $id]);
    }
}
