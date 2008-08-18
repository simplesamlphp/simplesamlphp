<?php

/**
 * This is a helper class for saving and loading state information.
 *
 * The state must be an associative array. This class will add additional keys to this
 * array. These keys will always start with 'SimpleSAML_Auth_State.'.
 *
 * It is also possible to add a restart URL to the state. If state information is lost, for
 * example because it timed out, or the user loaded a bookmarked page, the loadState function
 * will redirect to this URL. To use this, set $state[SimpleSAML_Auth_State::RESTART] to this
 * URL.
 *
 * Both the saveState and the loadState function takes in a $stage parameter. This parameter is
 * a security feature, and is used to prevent the user from taking a state saved one place and
 * using it as input a different place.
 *
 * The $stage parameter must be a unique string. To maintain uniqueness, it must be on the form
 * "<classname>.<identifier>" or "<module>:<identifier>".
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Auth_State {


	/**
	 * The index in the state array which contains the identifier.
	 */
	const ID = 'SimpleSAML_Auth_State.id';


	/**
	 * The index in the state array which contains the current stage.
	 */
	const STAGE = 'SimpleSAML_Auth_State.stage';


	/**
	 * The index in the state array which contains the restart URL.
	 */
	const RESTART = 'SimpleSAML_Auth_State.restartURL';


	/**
	 * Save the state.
	 *
	 * This function saves the state, and returns an id which can be used to
	 * retrieve it later. It will also update the $state array with the identifier.
	 *
	 * @param array &$state  The login request state.
	 * @param string $stage  The current stage in the login process.
	 * @return string  Identifier which can be used to retrieve the state later.
	 */
	public static function saveState(&$state, $stage) {
		assert('is_array($state)');
		assert('is_string($stage)');

		/* Save stage. */
		$state[self::STAGE] = $stage;

		if (!array_key_exists(self::ID, $state)) {
			$state[self::ID] = SimpleSAML_Utilities::generateID();
		}

		$id = $state[self::ID];

		/* Embed the restart URL in the state identifier, if it is available. */
		if (array_key_exists(self::RESTART, $state)) {
			assert('is_string($state[self::RESTART])');
			$return = $id . ':' . $state[self::RESTART];
		} else {
			$return = $id;
		}

		$serializedState = serialize($state);

		$session = SimpleSAML_Session::getInstance();
		$session->setData('SimpleSAML_Auth_State', $id, $serializedState, 60*60);

		return $return;
	}


	/**
	 * Retrieve saved state.
	 *
	 * This function retrieves saved state information. If the state information has been lost,
	 * it will attempt to restart the request by calling the restart URL which is embedded in the
	 * state information. If there is no restart information available, an exception will be thrown.
	 *
	 * @param string $id  State identifier (with embedded restart information).
	 * @param string $stage  The stage the state should have been saved in.
	 * @return array  State information.
	 */
	public static function loadState($id, $stage) {
		assert('is_string($id)');
		assert('is_string($stage)');

		$tmp = explode(':', $id, 2);
		$id = $tmp[0];
		if (count($tmp) === 2) {
			$restartURL = $tmp[1];
		} else {
			$restartURL = NULL;
		}

		$session = SimpleSAML_Session::getInstance();
		$state = $session->getData('SimpleSAML_Auth_State', $id);

		if ($state === NULL) {
			/* Could not find saved data. Attempt to restart. */

			if ($restartURL === NULL) {
				throw new Exception('State information lost, and no way to restart the request.');
			}

			SimpleSAML_Utilities::redirect($restartURL);
		}

		$state = unserialize($state);
		assert('is_array($state)');
		assert('array_key_exists(self::ID, $state)');
		assert('array_key_exists(self::STAGE, $state)');

		/* Verify stage. */
		if ($state[self::STAGE] !== $stage) {
			/* This could be a user trying to bypass security, but most likely it is just
			 * someone using the back-button in the browser. We try to restart the
			 * request if that is possible. If not, show an error.
			 */

			$msg = 'Wrong stage in state. Was \'' . $state[self::STAGE] .
				'\', shoud be \'' . $stage . '\'.';

			SimpleSAML_Logger::warning($msg);

			if ($restartURL === NULL) {
				throw new Exception($msg);
			}

			SimpleSAML_Utilities::redirect($restartURL);
		}

		return $state;
	}


	/**
	 * Delete state.
	 *
	 * This function deletes the given state to prevent the user from reusing it later.
	 *
	 * @param array &$state  The state which should be deleted.
	 */
	public static function deleteState(&$state) {
		assert('is_array($state)');

		if (!array_key_exists(self::ID, $state)) {
			/* This state hasn't been saved. */
			return;
		}

		$session = SimpleSAML_Session::getInstance();
		$session->deleteData('SimpleSAML_Auth_State', $state[self::ID]);
	}

}

?>