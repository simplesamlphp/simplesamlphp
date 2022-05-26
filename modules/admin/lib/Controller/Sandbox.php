<?php

declare(strict_types=1);

namespace SimpleSAML\Module\admin\Controller;

use SimpleSAML\Configuration;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;

/**
 * Controller class for the admin module.
 *
 * This class serves the 'sandbox' views available in the module.
 *
 * @package SimpleSAML\Module\admin
 */
class Sandbox
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;


    /**
     * Sandbox constructor.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use.
     * @param \SimpleSAML\Session $session The current user session.
     */
    public function __construct(Configuration $config, Session $session)
    {
        $this->config = $config;
        $this->session = $session;
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
