<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

/**
 * Ustream_Daemon_MultiInstanceTaskFactory
 */
interface Ustream_Daemon_MultiInstanceTaskFactory
{
	/**
	 * @param Ustream_Daemon_Daemon $daemon
	 * @param int  $instanceId
	 * @param int $instanceCount
	 *
	 * @return Ustream_Daemon_Task
	 */
	public function create(Ustream_Daemon_Daemon $daemon, $instanceId, $instanceCount);
}
