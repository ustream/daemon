<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

/**
 * Builder to create custom daemon util instances
 */
class Ustream_Daemon_Builder
{
	/**
	 * @var Ustream_Daemon_Config
	 */
	private $config;

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @static
	 * @return Ustream_Daemon_Builder
	 */
	public static function createDefault()
	{
		return new Ustream_Daemon_Builder(new Ustream_Daemon_Config());
	}

	/**
	 * @param Ustream_Daemon_Config $config
	 */
	public function __construct(Ustream_Daemon_Config $config)
	{
		$this->config = $config;
	}

	/**
	 * @param string $fileName
	 *
	 * @return Ustream_Daemon_Builder
	 */
	public function setFileNameAsIdentifier($fileName)
	{
		$this->id = pathinfo($fileName, PATHINFO_FILENAME);
		return $this;
	}

	/**
	 * @param string $id
	 * @return Ustream_Daemon_Builder
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @param float $seconds
	 *
	 * @return Ustream_Daemon_Builder
	 */
	public function sleepBetweenRuns($seconds)
	{
		$this->config->sleepBetweenRuns = $seconds;
		return $this;
	}

	/**
	 * @return Ustream_Daemon_Builder
	 */
	public function useMultipleInstances()
	{
		$this->config->multipleInstances = true;
		return $this;
	}

	/**
	 * @param int $instanceNumber
	 * @return Ustream_Daemon_Builder
	 */
	public function instanceNumber($instanceNumber)
	{
		$this->config->instanceNumber = $instanceNumber;
		return $this;
	}

	/**
	 * @param float $seconds
	 *
	 * @return Ustream_Daemon_Builder
	 */
	public function minumumSleep($seconds)
	{
		$this->config->minimumSleep = $seconds;
		return $this;
	}

	/**
	 * @param string $runDir
	 *
	 * @return Ustream_Daemon_Builder
	 */
	public function runDir($runDir)
	{
		$this->config->runDir = rtrim($runDir, '/');
		return $this;
	}

	/**
	 * @param string $logDir
	 *
	 * @return Ustream_Daemon_Builder
	 */
	public function logDir($logDir)
	{
		$this->config->logDir = rtrim($logDir, '/');
		return $this;
	}

	/**
	 * @param float $memoryThreshold
	 *
	 * @return Ustream_Daemon_Builder
	 */
	public function memoryThreshold($memoryThreshold)
	{
		$this->config->memoryThreshold = $memoryThreshold;
		return $this;
	}

	/**
	 * @param string $file
	 * @return Ustream_Daemon_Builder
	 */
	public function commonLog($file)
	{
		$this->config->customDaemonUtilLogFile = $file;
		return $this;
	}

	/**
	 * @param string $context
	 *
	 * @return $this
	 */
	public function context($context)
	{
		$this->config->context = $context;
		return $this;
	}

	/**
	 * @param array $listeners
	 *
	 * @return $this
	 */
	public function listeners($listeners)
	{
		$this->config->listeners = $listeners;
		return $this;
	}

	/**
	 * @param Ustream_Daemon_TaskFactory $taskFactory
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return Ustream_Daemon_Starter
	 */
	public function build(Ustream_Daemon_TaskFactory $taskFactory)
	{
		$this->validate();

		$daemon = new Ustream_Daemon_PreconfiguredTaskDelegator($this->id, $this->config);
		Ustream_Daemon_Logger::setInstance(new Ustream_Daemon_Logger($daemon));
		$daemon->setTask($taskFactory->createTaskFor($daemon));

		return $daemon;
	}

	/**
	 * @param callback $factory
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return Ustream_Daemon_Starter
	 */
	public function buildUsingCallbackFactory($factory)
	{
		$this->validate();

		if (!is_callable($factory)) {
			throw new InvalidArgumentException(sprintf('"%s" is not a valid callable', $factory));
		}

		$daemon = new Ustream_Daemon_PreconfiguredTaskDelegator($this->id, $this->config);
		Ustream_Daemon_Logger::setInstance(new Ustream_Daemon_Logger($daemon));
		$daemon->setTask(call_user_func($factory));

		return $daemon;
	}

	/**
	 * @param Ustream_Daemon_Task $task
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return Ustream_Daemon_Starter
	 */
	public function buildTask(Ustream_Daemon_Task $task)
	{
		$this->validate();

		$daemon = new Ustream_Daemon_PreconfiguredTaskDelegator($this->id, $this->config);
		Ustream_Daemon_Logger::setInstance(new Ustream_Daemon_Logger($daemon));
		$daemon->setTask($task);

		return $daemon;
	}

	/**
	 * @param Ustream_Daemon_MultiInstanceTaskFactory $taskFactory
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return Ustream_Daemon_Starter
	 */
	public function buildMultiInstance(Ustream_Daemon_MultiInstanceTaskFactory $taskFactory)
	{
		$this->validate();
		$opts = getopt("i:n:");

		if (!isset($opts['i']) || !is_numeric($opts['i']) || !isset($opts['n']) || !is_numeric($opts['n'])) {
			echo "Arguments: -i <0..n-1> -n <n>\nWhere n is the number of queue processors running\n";
			exit;
		}

		$this->config->multipleInstances = true;
		$this->config->instanceNumber = (int) $opts['i'];

		$daemon = new Ustream_Daemon_PreconfiguredTaskDelegator($this->id, $this->config);
		Ustream_Daemon_Logger::setInstance(new Ustream_Daemon_Logger($daemon));
		$daemon->setTask($taskFactory->create($daemon, (int) $opts['i'], (int) $opts['n']));

		return $daemon;
	}

	/**
	 * @throws InvalidArgumentException
	 *
	 * @return void
	 */
	private function validate()
	{
		if ($this->id == null) {
			throw new InvalidArgumentException('Id cannot be null');
		}
	}
}
