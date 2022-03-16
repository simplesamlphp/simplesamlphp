<?php

/**
 * Session storage in the data store.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Store\StoreInterface;

class SessionHandlerStore extends SessionHandlerCookie
{
    /**
     * The data store we save the session to.
     *
     * @var \SimpleSAML\Store\StoreInterface
     */
    private StoreInterface $store;


    /**
     * Initialize the session.
     *
     * @param \SimpleSAML\Store\StoreInterface $store The store to use.
     */
    protected function __construct(StoreInterface $store)
    {
        parent::__construct();

        $this->store = $store;
    }


    /**
     * Load a session from the data store.
     *
     * @param string|null $sessionId The ID of the session we should load, or null to use the default.
     *
     * @return \SimpleSAML\Session|null The session object, or null if it doesn't exist.
     */
    public function loadSession(?string $sessionId): ?Session
    {
        if ($sessionId === null) {
            $sessionId = $this->getCookieSessionId();
            if ($sessionId === null) {
                // no session cookie, nothing to load
                return null;
            }
        }

        $session = $this->store->get('session', $sessionId);
        if ($session !== null) {
            Assert::isInstanceOf($session, Session::class);
            return $session;
        }

        return null;
    }


    /**
     * Save a session to the data store.
     *
     * @param \SimpleSAML\Session $session The session object we should save.
     */
    public function saveSession(Session $session): void
    {
        if ($session->isTransient()) {
            // transient session, nothing to save
            return;
        }

        /** @var string $sessionId */
        $sessionId = $session->getSessionId();

        $config = Configuration::getInstance();
        $sessionDuration = $config->getOptionalInteger('session.duration', 8 * 60 * 60);
        $expire = time() + $sessionDuration;

        $this->store->set('session', $sessionId, $session, $expire);
    }
}
