<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

/**
 * Ustream_Daemon_PreconfiguredTaskDelegator
 */
class Ustream_Daemon_PreconfiguredTaskDelegator extends Ustream_Daemon_Daemon implements Ustream_Daemon_Starter
{
	/**
	 * @var Ustream_Daemon_Task
	 */
	private $task;

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var Ustream_Daemon_Config
	 */
	private $config;

	/**
	 * @param string             $id
	 * @param Ustream_Daemon_Config $config
	 */
	public function __construct($id, Ustream_Daemon_Config $config)
	{
		if ($config->customDaemonUtilLogFile !== null) {
			$this->daemonUtilLogFile = $config->customDaemonUtilLogFile;
		}

		parent::__construct();

		$this->id                   = $id;
		$this->useMultipleInstances = $config->multipleInstances;
		$this->config               = $config;
		$this->forceDirectory       = $this->config->logDir;

		$context = $config->context;

		$this->setPidFileLocation('%s/%s-%s.pid', $config->runDir, $this->id, $context);
		$this->setLogFile(sprintf('%s/%s', $config->logDir, $this->id), $context);

		$this->sleepBetweenRuns = $config->sleepBetweenRuns;
		$this->minimumSleep     = $config->minimumSleep;

		$this->memoryThreshold = $config->memoryThreshold;
		$eventListeners = $config->listeners;

		$this->addListeners($eventListeners);
	}

	/**
	 * Name column for the daemon monitor
	 *
	 * @return string daemon id.
	 */
	public function getName()
	{
		return $this->id;
	}

	/**
	 * @return \Ustream_Daemon_Task
	 */
	public function getTask()
	{
		return $this->task;
	}

	/**
	 * @return string
	 */
	public function getPidFilePath()
	{
		return $this->pidFileLocation;
	}

	/**
	 * @return string
	 */
	public function getLogFilePath()
	{
		return $this->getLogFileName();
	}

	/**
	 * @param Ustream_Daemon_Task $task
	 * @return void
	 */
	public function setTask(Ustream_Daemon_Task $task)
	{
		$this->task = $task;
	}

	/**
	 * Do task
	 *
	 * @return void
	 */
	protected function doTask()
	{
		try {
			$this->task->doTask();
		} catch (Ustream_Daemon_StopException $e) {
			$this->stop();
		}
	}

	/**
	 * @return void
	 */
	protected function setInstanceNumber()
	{
		$this->instanceNumber = $this->config->instanceNumber;
	}
}
