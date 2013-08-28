<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

/**
 * Ustream_Daemon_TaskFactory
 */
interface Ustream_Daemon_TaskFactory
{
	/**
	 * @abstract
	 * @param Ustream_Daemon_Daemon $daemon
	 * @return Ustream_Daemon_Task
	 */
	public function createTaskFor(Ustream_Daemon_Daemon $daemon);
}
