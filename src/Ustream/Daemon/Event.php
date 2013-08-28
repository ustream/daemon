<?php
/**
 * @author oker <takacs.zsolt@ustream.tv>
 */

/**
 * Event
 *
 * @author oker <takacs.zsolt@ustream.tv>
 */
abstract class Ustream_Daemon_Event
{
	const START = 'ustream.daemon.start';

	const TASK_DONE = 'ustream.daemon.task_done';
}