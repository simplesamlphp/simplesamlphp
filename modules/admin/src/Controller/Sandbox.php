<?php

declare(strict_types=1);

namespace SimpleSAML\Module\admin\Controller;

use SimpleSAML\{Configuration, Session};
use SimpleSAML\XHTML\Template;

use function time;

/**
 * Controller class for the admin module.
 *
 * This class serves the 'sandbox' views available in the module.
 *
 * @package SimpleSAML\Module\admin
 */
class Sandbox
{
    /**
     * Sandbox constructor.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use.
     * @param \SimpleSAML\Session $session The current user session.
     */
    public function __construct(
        protected Configuration $config,
        protected Session $session,
    ) {
    }


    /**
     * Display the sandbox page
     *
     * @return \SimpleSAML\XHTML\Template
     */
    public function main(): Template
    {
        $template = new Template($this->config, 'sandbox.twig');
        $template->data['pagetitle'] = 'Sandbox';
        $template->data['sometext'] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus.' .
            ' Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit.' .
            ' Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. ' .
            'Nam tincidunt congue enim, ut porta lorem lacinia consectetur.';
        $template->data['remaining'] = $this->session->getAuthData('admin', 'Expire') - time();
        $template->data['logout'] = null;
        return $template;
    }
}
