<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

namespace Ustream\DaemonTest;

/**
 * DaemonControl
 */
class DaemonControl
{
	const START_COMMAND = 'nohup %s > /tmp/daemon_out 2>> /tmp/daemon_error < /dev/null &';

	/**
	 * @var string
	 */
	private $daemonFile;

	/**
	 * @var string
	 */
	private $pidFile;

	/**
	 * @param string $daemonFile
	 * @param string $pidFile
	 */
	public function __construct($daemonFile, $pidFile)
	{
		$this->daemonFile = $daemonFile;
		$this->pidFile = $pidFile;
	}

	/**
	 * @return void
	 */
	public function start()
	{
		$command = sprintf(self::START_COMMAND, $this->daemonFile);
		exec($command);
	}

	/**
	 * @return int
	 */
	public function getPid()
	{
		return (int) file_get_contents($this->pidFile);
	}

	/**
	 * @return void
	 */
	public function stop()
	{
		posix_kill($this->getPid(), SIGTERM);
	}
}

?>