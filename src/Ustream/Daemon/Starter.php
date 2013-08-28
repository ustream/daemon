<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

/**
 * Ustream_Daemon_Starter
 */
interface Ustream_Daemon_Starter
{
	/**
	 * @return bool
	 */
	public function start();

	/**
	 * @abstract
	 * @return string
	 */
	public function getPidFilePath();

	/**
	 * @abstract
	 * @return string
	 */
	public function getLogFilePath();
}
