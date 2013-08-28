<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

/**
 * Ustream_Daemon_Runner
 */
class Ustream_Daemon_Runner
{
	/**
	 * @param string $runDir
	 * @param string $context
	 * @param string $iniDir
	 * @param array  $listeners
	 *
	 * @return void
	 *
	 */
	public function runDaemon($runDir, $context, $iniDir, $listeners)
	{
		$opts = getopt(
			'h::',
			array(
				'defaults:',
				'id:',
				'factory:',
				'sleep:',
				'min-sleep:',
				'instance:',
				'config:',
				'memory-limit:',
			)
		);
		if (array_key_exists('h', $opts)) {
			echo <<<HELP
Usage:

	  batch/daemon.php --id <name>

Required:
 --id <name>           Uniqe name that will be used in the pid/log file name and used to load default config

Default configuration:
 --config <file>       Custom configuration loaded from ini file

Factory method:
 --factory <callable>  Callable that will be used to create the Ustream_Daemon_Task implementation

Timing:
 --sleep <secs>        Sleep between subsequent task executions (minus the task runtime) 1 sec by default
 --min-sleep <secs>    Guaranteed sleep after each task 1 sec by default

Multi instance mode:
 --instance <num>      Run in multi instance mode and use <num> as instance number, starting from 0

PHP settings:
 --memory-limit <value>  Memory limit

HELP;
			exit;
		}

		try {
			if (!array_key_exists('id', $opts)) {
				throw new InvalidArgumentException('--id parameter missing');
			}

			if (array_key_exists('config', $opts)) {
				echo 'Using configuration from: ' . $opts['config'] . PHP_EOL;
				$opts = array_merge($this->loadDefaults($opts['config']), $opts);
			}

			$defaultConfig = sprintf($iniDir.'/%s.ini', $opts['id']);
			if (file_exists($defaultConfig)) {
				echo 'Using configuration from: ' . $defaultConfig.PHP_EOL;
				$opts = array_merge($this->loadDefaults($defaultConfig), $opts);
			}

			$defaultConfig = new Ustream_Daemon_Config();
			$defaults = array(
				'sleep' => $defaultConfig->sleepBetweenRuns,
				'min-sleep' => $defaultConfig->minimumSleep,
				'memory-threshold' => $defaultConfig->memoryThreshold,
				'log-dir' => $defaultConfig->logDir,
				'comon-log' => $defaultConfig->customDaemonUtilLogFile,
			);
			$opts = array_merge($defaults, $opts);

			if (!array_key_exists('factory', $opts)) {
				throw new InvalidArgumentException('--factory parameter missing');
			}

			if (array_key_exists('memory-limit', $opts)) {
				ini_set('memory_limit', $opts['memory-limit']);
			}

			$builder = Ustream_Daemon_Builder::createDefault()
				->setId($opts['id'])
				->sleepBetweenRuns($opts['sleep'])
				->minumumSleep($opts['min-sleep'])
				->memoryThreshold($opts['memory-threshold'])
				->runDir($runDir)
				->logDir($opts['log-dir'])
				->commonLog($opts['common-log'])
				->context($context)
				->listeners($listeners);

			if (array_key_exists('instance', $opts)) {
				$builder->useMultipleInstances()->instanceNumber((int) $opts['instance']);
			}

			$daemon = $builder->buildUsingCallbackFactory($opts['factory']);

			echo 'Starting daemon if it\'s not already running'.PHP_EOL;
			echo sprintf('Sleep between runs / minimum sleep: %f / %f', $opts['sleep'], $opts['min-sleep']).PHP_EOL;
			echo sprintf('Pid file: %s', $daemon->getPidFilePath()).PHP_EOL;
			echo sprintf('Log file: %s', $daemon->getLogFilePath()).PHP_EOL;

			if (file_exists($daemon->getPidFilePath())) {
				$message = sprintf('Pid file [%s] already exists', $daemon->getPidFilePath());
				trigger_error($message, E_USER_NOTICE);
				echo $message.PHP_EOL;
			}
			$daemon->start();

		} catch (Exception $e) {
			echo $e;
			exit(1);
		}
	}

	/**
	 * @param string $defaultsFile
	 * @return array
	 * @throws InvalidArgumentException
	 */
	private function loadDefaults($defaultsFile)
	{
		if (!file_exists($defaultsFile)) {
			throw new InvalidArgumentException('Cannot read defaults file: ' . $defaultsFile);
		}
		return parse_ini_file($defaultsFile);
	}
}
