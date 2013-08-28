<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

namespace Ustream\DaemonTest;

/**
 * MultiInstanceDaemonControl
 */
class MultiInstanceDaemonControl
{
	/**
	 * @var array
	 */
	private $instances;

	/**
	 * @var string
	 */
	private $daemonFile;

	/**
	 * @var string
	 */
	private $pidFileTemplate;

	/**
	 * @param string $daemonFile
	 * @param string $pidFileTemplate
	 * @param array $instances
	 */
	public function __construct($daemonFile, $pidFileTemplate, $instances)
	{
		$this->daemonFile = $daemonFile;
		$this->instances = $instances;
		$this->pidFileTemplate = $pidFileTemplate;
	}

	/**
	 * @param int $instanceId
	 */
	public function start($instanceId)
	{
		$command = sprintf(
			DaemonControl::START_COMMAND,
			sprintf('%s -i%d -n%d', $this->daemonFile, $instanceId, count($this->instances))
		);
		exec($command);
	}

	/**
	 * @return void
	 */
	public function startAll()
	{
		array_walk($this->instances, array($this, 'start'));
	}

	/**
	 * @param int $instanceId
	 *
	 * @return int
	 */
	public function getPid($instanceId)
	{
		return (int)file_get_contents($this->getPidFileName($instanceId));
	}

	/**
	 * @param int $instanceId
	 *
	 * @return string
	 */
	public function getPidFileName($instanceId)
	{
		return sprintf($this->pidFileTemplate, $instanceId);
	}

	/**
	 * @param int $instanceId
	 */
	public function stop($instanceId)
	{
		posix_kill($this->getPid($instanceId), SIGTERM);
	}

	/**
	 * @return void
	 */
	public function stopAll()
	{
		array_walk($this->instances, array($this, 'stop'));
	}
}

?>