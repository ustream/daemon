<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

/**
 * Task
 */
interface Ustream_Daemon_Task
{
	/**
	 * @return void
	 *
	 * @throws Ustream_Daemon_StopException If you want to exit, throw this
	 */
	public function doTask();
}
