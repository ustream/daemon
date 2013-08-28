<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

/**
 * CallbackTask
 */
class Ustream_Daemon_CallbackTask implements Ustream_Daemon_Task
{
	/**
	 * @var callback
	 */
	private $callback;

	/**
	 * @static
	 * @return Ustream_Daemon_CallbackTask
	 */
	public static function nullTask()
	{
		return new Ustream_Daemon_CallbackTask(function () {
			Ustream_Daemon_Logger::getInstance()->debug('null');
		});
	}

	/**
	 * @param callback $callback
	 */
	public function __construct($callback)
	{
		$this->callback = $callback;
	}

	/**
	 * Do task
	 *
	 * @return void
	 */
	public function doTask()
	{
		call_user_func($this->callback);
	}
}
