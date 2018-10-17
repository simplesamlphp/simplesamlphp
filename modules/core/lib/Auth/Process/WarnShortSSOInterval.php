<?php

namespace SimpleSAML\Module\core\Auth\Process;

/**
 * Give a warning to the user if we receive multiple requests in a short time.
 *
 * @package SimpleSAMLphp
 */

class WarnShortSSOInterval extends \SimpleSAML\Auth\ProcessingFilter
{
    /**
     * Process a authentication response.
     *
     * This function checks how long it is since the last time the user was authenticated.
     * If it is to short a while since, we will show a warning to the user.
     *
     * @param array $state  The state of the response.
     */
    public function process(&$state)
    {
        assert(is_array($state));

        if (!array_key_exists('PreviousSSOTimestamp', $state)) {
            /*
             * No timestamp from the previous SSO to this SP. This is the first
             * time during this session.
             */
            return;
        }

        $timeDelta = time() - $state['PreviousSSOTimestamp'];
        if ($timeDelta >= 10) {
            // At least 10 seconds since last attempt
            return;
        }

        if (array_key_exists('Destination', $state) && array_key_exists('entityid', $state['Destination'])) {
            $entityId = $state['Destination']['entityid'];
        } else {
            $entityId = 'UNKNOWN';
        }

        \SimpleSAML\Logger::warning('WarnShortSSOInterval: Only '.$timeDelta.
            ' seconds since last SSO for this user from the SP '.var_export($entityId, true));

        // Save state and redirect
        $id = \SimpleSAML\Auth\State::saveState($state, 'core:short_sso_interval');
        $url = \SimpleSAML\Module::getModuleURL('core/short_sso_interval.php');
        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url, ['StateId' => $id]);
    }
}
