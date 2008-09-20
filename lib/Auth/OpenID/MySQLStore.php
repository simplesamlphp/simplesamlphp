<?php

/**
 * A MySQL store.
 *
 * @package OpenID
 */

/**
 * Require the base class file.
 */
require_once "Auth/OpenID/SQLStore.php";

/**
 * An SQL store that uses MySQL as its backend.
 *
 * @package OpenID
 */
class Auth_OpenID_MySQLStore extends Auth_OpenID_SQLStore {
    /**
     * @access private
     */
    function setSQL()
    {
        $this->sql['nonce_table'] =
            "CREATE TABLE %s (\n".
            "  server_url VARCHAR(2047),\n".
            "  timestamp INTEGER,\n".
            "  salt CHAR(40),\n".
            "  UNIQUE (server_url(255), timestamp, salt)\n".
            ") TYPE=InnoDB";

        $this->sql['assoc_table'] =
            "CREATE TABLE %s (\n".
            "  server_url BLOB,\n".
            "  handle VARCHAR(255),\n".
            "  secret BLOB,\n".
            "  issued INTEGER,\n".
            "  lifetime INTEGER,\n".
            "  assoc_type VARCHAR(64),\n".
            "  PRIMARY KEY (server_url(255), handle)\n".
            ") TYPE=InnoDB";

        $this->sql['set_assoc'] =
            "REPLACE INTO %s VALUES (?, ?, !, ?, ?, ?)";

        $this->sql['get_assocs'] =
            "SELECT handle, secret, issued, lifetime, assoc_type FROM %s ".
            "WHERE server_url = ?";

        $this->sql['get_assoc'] =
            "SELECT handle, secret, issued, lifetime, assoc_type FROM %s ".
            "WHERE server_url = ? AND handle = ?";

        $this->sql['remove_assoc'] =
            "DELETE FROM %s WHERE server_url = ? AND handle = ?";

        $this->sql['add_nonce'] =
            "INSERT INTO %s (server_url, timestamp, salt) VALUES (?, ?, ?)";

        $this->sql['get_expired'] =
            "SELECT server_url FROM %s WHERE issued + lifetime < ?";
    }

    /**
     * @access private
     */
    function blobEncode($blob)
    {
        return "0x" . bin2hex($blob);
    }
}

?>