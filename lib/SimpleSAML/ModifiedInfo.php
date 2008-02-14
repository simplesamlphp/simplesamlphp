<?php

/**
 * The ModifiedInfo interface allows an object to export information about
 * whether it has been modified since it was deserialized or not.
 */
interface SimpleSAML_ModifiedInfo {

	/**
	 * This function is used to determine if this object has changed
	 * since it was deserialized.
	 *
	 * @return TRUE if it has changed, FALSE if not.
	 */
	public function isModified();

}
?>