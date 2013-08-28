<?php
/**
 * Daemon_Util
 * @author Backend <php@ustream.tv>
 */
/*
 * Log message levels
 */
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Daemon base class
 *
 * Requirements:
 * Unix like operating system
 * PHP 4 >= 4.3.0 or PHP 5
 * PHP compiled with:
 * --enable-sigchild
 * --enable-pcntl
 */
abstract class Ustream_Daemon_Daemon
{
	/**
	 * @var string Parent process type
	 */
	const PROCESS_PARENT = 'p';

	/**
	 * @var string Child process type
	 */
	const PROCESS_CHILD  = 'c';

	/**
	 * Override logfile extension.
	 *
	 * @var string
	 */
	const FORCED_LOG_EXTENSION = 'log';

	/**
	 * Override log directory.
	 *
	 * @var string
	 */
	const FORCED_LOG_DIRECTORY = '/var/log/custom_php/';

	/**
	 * User ID
	 *
	 * @var int
	 * @since 1.0
	 */
	private $userID = 0;

	/**
	 * Group ID
	 *
	 * @var integer
	 * @since 1.0
	 */
	private $groupID = 0;

	/**
	 * Terminate daemon when set identity failure ?
	 *
	 * @var bool
	 * @since 1.0.3
	 */
	private $requireSetIdentity = false;

	/**
	 * Path to PID file. Must implement
	 *
	 * @var string
	 * @since 1.0.1
	 */
	public $pidFileLocation = null;

	/**
	 * Home path
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $homePath = '/';

	/**
	 * Current process ID
	 *
	 * @var int
	 * @since 1.0
	 */
	protected $pid = 0;

	/**
	 * Is this process a children
	 *
	 * @var boolean
	 * @since 1.0
	 */
	private $isChildren = false;

	/**
	 * Is daemon running
	 *
	 * @var boolean
	 * @since 1.0
	 */
	private $isRunning = false;

	/**
	 * Set true if pid file created. In constructur defaults to false
	 *
	 * @var boolean
	 */
	private $pidFileCreated = false;

	/**
	 * The log file for daemon util
	 *
	 * @var string
	 */
	protected $daemonUtilLogFile = '/var/log/custom_php/daemon_util.log';

	/**
	 * use setLogFile to give value for this. writeLog needs a qualified file name
	 * @var string
	 */
	private $logFileAquired = null;

	/**
	 * when a child created with the same class as parent, set this flag to
	 * Ustream_Daemon_Daemon::PROCESS_CHILD
	 *
	 * @var string process type
	 */
	protected $parentOrChild = 'p';

	/**
	 * How much to wait between to doTasks (in seconds). From this the runtime of doTasks will be subtracted.
	 *
	 * @var $sleepBetweenRuns integer
	 */
	protected $sleepBetweenRuns = 0;

	/**
	 * Minimal amount to wait before the next doTask.
	 * eg: if sleepBetweenRuns is 0 (because doTask ran longer)
	 *
	 * @var $minimumSleep integer
	 */
	protected $minimumSleep = 0;

	/**
	 * Should the daemon run in multiple instances?
	 * You have to specify the numeric identifier
	 * of the instance starting from 0 with the -i flag.
	 *
	 * @var boolean
	 */
	protected $useMultipleInstances = false;

	/**
	 * If running in multiinstance mode,
	 * the numeric identifier of the current instance.
	 *
	 * @var integer
	 */
	protected $instanceNumber = 0;

	/**
	 * @var int percent of used memory before daemon should be stopped
	 */
	protected $memoryThreshold = 70;

	/**
	 * @var string Directory for logs
	 */
	protected $forceDirectory;

	/**
	 * @var EventDispatcher
	 */
	private $eventDispatcher;

	/**
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		set_time_limit(0);
		ob_start();
		ob_implicit_flush();

		$this->eventDispatcher = new EventDispatcher();

		$this->pidFileCreated = false;
		$this->pid            = posix_getpid();
		$this->forceDirectory = self::FORCED_LOG_DIRECTORY;

		$this->writeDaemonLog('Daemon created.');

		$this->monitorCommandLastReceived = time();

		$this->initialized = true;
	}

	/**
	 * Starts daemon
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function start()
	{
		$memoryLimit = $this->getMemoryLimit();
		
		if (!$this->initialized || !$this->daemonize()) {
			return false;
		}

		$event = new GenericEvent($this, array());
		$this->eventDispatcher->dispatch(Ustream_Daemon_Event::START, $event);

		$this->isRunning = true;
		register_shutdown_function(array(&$this, 'releaseDaemon'));

		while ($this->isRunning) {
            $startTime = microtime(true);

			$this->doTask();
			$this->signalDispatch();

			$event = new GenericEvent(
				$this,
				array(
					'delay' => ($this->sleepBetweenRuns > 0) ? microtime(true) - $startTime : 0, //TODO: better name?
				)
			);
			$this->eventDispatcher->dispatch(Ustream_Daemon_Event::TASK_DONE, $event);

			$this->sleepIfNecessary($startTime);
			$this->signalDispatch();

			if ($memoryLimit > 0) {
				$this->checkMemoryUsage($memoryLimit);
			}
		}

		return true;
	}

	/**
	 * Stops daemon
	 *
	 * @since 1.0
	 * @return void
	 */
	public function stop()
	{
		$this->isRunning = false;
	}

	/**
	 * Run or not
	 *
	 * @return boolean
	 */
	public function isRunning()
	{
		return $this->isRunning;
	}

	/**
	 * calls signal handlers for pending signals
	 *
	 * @return void
	 */
	public function signalDispatch()
	{
		if (function_exists('pcntl_signal_dispatch')) {
			pcntl_signal_dispatch();
		}
	}

	/**
	 * Do task
	 *
	 * @since 1.0
	 * @return void
	 */
	protected abstract function doTask();

	/**
	 * @return mixed
	 */
	abstract public function getTask();

	/**
	 * wait between two runs if needed
	 *
	 * @param int $startTime
	 * @return void
	 */
	private function sleepIfNecessary($startTime)
	{
		// Calculate the sleep between runs.
		$delay = $this->minimumSleep * 1000000;
		if ($this->sleepBetweenRuns > 0) {
			$delay = ($this->sleepBetweenRuns * 1000000) - ((microtime(true) - $startTime) * 1000000);
		}
		// Less delay then minimumSleep.
		if ($delay < ($this->minimumSleep * 1000000)) {
			$delay = $this->minimumSleep * 1000000;
		}
		// Sleep , if we have to .
		if ($delay > 0 && $this->isRunning) {
			usleep($delay);
		}
	}

	/**
	 * checks memory usage and exits if too much
	 *
	 * @param int $memoryLimit
	 * @return void
	 */
	private function checkMemoryUsage($memoryLimit)
	{
		$memoryUsage = memory_get_peak_usage(true);
		if ($memoryUsage / $memoryLimit > $this->memoryThreshold / 100) {
			$this->stop();
			trigger_error(
				"The ({$this->getName()}) almost ran out of memory. " .
					"Usage: {$memoryUsage}, limit: {$memoryLimit}, threshold: {$this->memoryThreshold}. " .
					"It will stop now. Monit will start it again"
			);
		}
	}

	/**
	 * Daemonize
	 *
	 * Several rules or characteristics that most daemons possess:
	 * 1) Check is daemon already running
	 * 2) Fork child process
	 * 3) Sets identity
	 * 4) Make current process a session laeder
	 * 5) Write process ID to file
	 * 6) Change home path
	 * 7) umask(0)
	 *
	 * @access private
	 * @since 1.0
	 * @return boolean
	 */
	private function daemonize()
	{
		if (ob_get_length()) {
			ob_end_flush();
		}

		if ($this->isDaemonRunning()) {
			// Deamon is already running. Exiting
			return false;
		}

		if (!$this->fork()) {
			// Coudn't fork. Exiting.
			return false;
		}

		if (!$this->setIdentity() && $this->requireSetIdentity) {
			// Required identity set failed. Exiting
			return false;
		}

		if (!posix_setsid()) {
			return false;
		}

		if (!$fileHandler = fopen($this->pidFileLocation, 'w')) {
			return false;
		} else {
			$this->writeDaemonLog(
				'Creating pid file! pidfileLocation: %s | Pid is %d.',
				$this->pidFileLocation,
				$this->pid
			);

			fputs($fileHandler, $this->pid);
			fclose($fileHandler);

			$this->pidFileCreated = true;
		}

		@chdir($this->homePath);
		umask(0);

		pcntl_signal(SIGCHLD, array($this, 'sigHandler'));
		pcntl_signal(SIGTERM, array($this, 'sigHandler'));
		pcntl_signal(SIGUSR1, array($this, 'sigHandler'));
		pcntl_signal(SIGUSR2, array($this, 'sigHandler'));
		pcntl_signal(SIGQUIT, array($this, 'sigHandler'));
		pcntl_signal(SIGHUP,  array($this, 'sigHandler'));

		return true;
	}

	/**
	 * Signals handler
	 *
	 * @param integer $sigNo
	 *
	 * @return void
	 */
	public function sigHandler($sigNo)
	{
		switch ($sigNo) {
			case SIGTERM:   // Shutdown
				exit();
				break;
			case SIGCHLD:   // Halt
				while (pcntl_waitpid(-1, $status, WNOHANG) > 0) {
				}
				break;
			case SIGQUIT:   // stop, but wait till child finish its doTask()
				$this->callSIGQUIT();
				$this->stop();
				break;
			case SIGHUP:   // call reload on child
				$this->callSIGHUP();
				break;
			case SIGUSR1:   // user defined
				$this->callSIGUSR1();
				break;
			case SIGUSR2:   // user defined
				$this->callSIGUSR2();
				break;
			default:
				break;
		}
	}

	/**
	 * Checks if daemon is already running
	 *
	 * @access private
	 * @since 1.0.3
	 * @return bool
	 */
	public function isDaemonRunning()
	{
		$oldPid = @file_get_contents($this->pidFileLocation);
		// posix_kill 0, check pid for existence
		return $oldPid !== false && posix_kill(trim($oldPid), 0);
	}

	/**
	 * Forks process
	 *
	 * @access private
	 * @since 1.0
	 * @return bool
	 */
	private function fork()
	{
		$pid = pcntl_fork();
		// Error happend.
		if (-1 == $pid) {
			$this->writeDaemonLog('fork called. result: -1.');
			return false;
		}

		// Parent thread.
		if ($pid) {
			$this->writeDaemonLog('fork called. child pid: %d.', $pid);
			exit();
		}

		// Child thread.
		$this->pid = posix_getpid();
		$this->isChildren = true;
		$this->writeDaemonLog("fork called.");

		return true;
	}

	/**
	 * Sets identity of a daemon and returns result
	 *
	 * @access private
	 * @since 1.0
	 * @return bool
	 */
	private function setIdentity()
	{
		return posix_setgid($this->groupID) && posix_setuid($this->userID);
	}

	/**
	 * Releases daemon pid file
	 * This method is called on exit (destructor like)
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function releaseDaemon()
	{
		$posixPid = posix_getpid();
		$this->writeDaemonLog('releaseDaemon called.');

		if ($this->pidFileCreated === true) {
			$pidfileContent = file_get_contents($this->pidFileLocation);
			if ($posixPid == $pidfileContent) {
				$this->writeDaemonLog(
					'releaseDaemon unlinking pidFile! pidfileLocation: %s | Pid is %d.',
					$this->pidFileLocation,
					$this->pid
				);
				unlink($this->pidFileLocation);
			} else {
				$this->writeDaemonLog(
					'releaseDaemon unlinking called, but no pid file was deleted! pidfileLocation: %s | Pid is %d.',
					$this->pidFileLocation,
					$this->pid
				);
			}
		}
	}

	/**
	 * @return boolean
	 */
	public function isMultiInstance()
	{
		return $this->useMultipleInstances;
	}

	/**
	 * @return int
	 */
	public function getInstanceNumber()
	{
		return $this->instanceNumber;
	}

	/**
	 * @return int
	 */
	public function getSleepBetweenRuns()
	{
		return $this->sleepBetweenRuns;
	}

	/**
	 * Creates a child process and starts callback function.
	 *
	 * @param callback $callback  Callback function.
	 *
	 * @return integer  Process id of the started child.
	 */
	protected function createChild($callback)
	{
		$pid = pcntl_fork();
		switch ($pid) {
			case -1:
				return false;

			case 0:
				// Get args for the callback function.
				$args = func_get_args();
				array_shift($args);
				// Call callback function.
				call_user_func_array($callback, $args);
				return posix_getpid();

			default:
				return $pid;
		}
	}

	/**
	 * implement this method if you want to catch the SIGQUIT event in your daemon
	 *
	 * @return void
	 */
	protected function callSIGQUIT()
	{
		print "Implement this in child if want to catch SIGQUIT!\n"; flush();
	}
	/**
	 * implement this method if you want to catch the SIGHUP event in your daemon
	 *
	 * @return void
	 */
	protected function callSIGHUP()
	{
		print "Implement this in child if want to catch SIGHUP!\n"; flush();
	}
	/**
	 * implement this method if you want to catch the SIGUSR1 event in your daemon
	 *
	 * @return void
	 */
	protected function callSIGUSR1()
	{
		print "Implement this in child if want to catch SIGUSE1!\n"; flush();
	}
	/**
	 * implement this method if you want to catch the SIGUSR2 event in your daemon
	 *
	 * @return void
	 */
	protected function callSIGUSR2()
	{
		print "Implement this in child if want to catch SIGUSR2!\n"; flush();
	}

	/**
	 * Set the pidFile location
	 * @param string $pidFile        The string with parameters for sprintf, like: '/var/run/mydaemon-%s.pid';
	 * @param string $parameter1     The parameter 1 for $logFile
	 *
	 * @return void
	 */
	protected function setPidFileLocation($pidFile, $parameter1 = null)
	{
		$args = func_get_args();
		$pidFile = array_shift($args);

		if ($this->useMultipleInstances) {
			$this->setInstanceNumber();
			$pidFile = $this->addStringToFileName($pidFile, $this->instanceNumber);
		}

		$this->pidFileLocation = vsprintf($pidFile, $args);
	}

	/**
	 * @return void
	 */
	protected function setInstanceNumber()
	{
		$this->instanceNumber = 0;
		$opts = getopt("i:");
		if (isset($opts['i'])) {
			$this->instanceNumber = (int)$opts['i'];
		}
	}

	/**
	 * Set the logfile for writeLog
	 * @param string $logFilePrefix   The prefix for logFileName, like: '/var/log/mydaemon'
	 * @param string $context         The context of daemon. If set, logFile name is extended whith this
	 * @return void
	 */
	protected function setLogFile($logFilePrefix, $context = null)
	{
		$this->logFileAquired = $logFilePrefix;
		if (!empty($context)) {
			$this->logFileAquired = $this->addStringToFileName($this->logFileAquired, $context);
		}
		$logDir = dirname($this->logFileAquired);
		if (!is_dir($logDir) && !mkdir($logDir, 0775, true)) {
			$msg = 'Unable to create logfile dir ['.$logDir.']!';
			$this->writeDaemonLog($msg);
			$this->stop();
			die($msg);
		}
	}

	/**
	 * generates the logfile name
	 * @return string
	 */
	protected function getLogFileName()
	{
		if (empty($this->logFileAquired)) {
			return null;
		}
		return $this->addStringToFileName(
			$this->logFileAquired, null, self::FORCED_LOG_EXTENSION, $this->forceDirectory
		);
	}

	/**
	 * Appends string to the end of the filename.
	 *
	 * @param string $file             File path.
	 * @param string $string           String to append to the end of filename.
	 * @param string $forceExtension   Override file extension.
	 * @param string $forceDirectory   Override directory.
	 *
	 * @return string
	 */
	protected function addStringToFileName($file, $string, $forceExtension = null, $forceDirectory = null)
	{
		$pathInfo = pathinfo($file);

		if (!empty($forceExtension)) {
			$pathInfo['extension'] = $forceExtension;
		}
		if (!empty($forceDirectory)) {
			$pathInfo['dirname'] = $forceDirectory;
		}

		$file = '';
		// Directory.
		if (!empty($pathInfo['dirname']) && $pathInfo['dirname'] != ".") {
			$file = rtrim($pathInfo['dirname'], "/")."/";
		}
		// Filename.
		$file .= $pathInfo['filename'];
		if (!is_null($string)) {
			$file .= "-".$string;
		}
		// Extension.
		if (!empty($pathInfo['extension'])) {
			$file .= '.'.$pathInfo['extension'];
		}

		return $file;
	}

	/**
	 * writes to a log file specified with setLogFile()
	 * @return bool
	 */
	public function writeLog()
	{
		$fileName = $this->getLogFileName();
		if ($fileName == null) {
			$this->writeDaemonLog('writeLog failed, not logFileAquired set! Use setLogFile to specify your daemons log file');
			return false;
		}
		$args = func_get_args();
		$argc = count($args);
		switch ($argc) {
			case 0:
				return false;
			case 1:
				array_unshift($args, '%s');
				break;
			default:
				break;
		}
		$args[0] = trim($args[0]);
		$pid = posix_getpid();
		$instanceId = '';
		if ($this->useMultipleInstances) {
			$instanceId = $this->instanceNumber . '> ';
		}
		$string = date('m/d/Y H:i:s').
			" {$this->parentOrChild} [{$pid}] {$instanceId}".
			call_user_func_array('sprintf', $args) . "\n";
		file_put_contents($fileName, $string, FILE_APPEND);
	}

	/**
	 * @return EventDispatcher
	 */
	public function getEventDispatcher()
	{
		return $this->eventDispatcher;
	}

	/**
	 * @return int
	 */
	public function getPid()
	{
		return $this->pid;
	}

	/**
	 * writes to a log file
	 * @return void
	 */
	public function writeDaemonLog()
	{
		$args = func_get_args();
		$pid = posix_getpid();

		$string = sprintf(
			"%s - %s%s [%d] - %s\n%s\n",
			date('m/d/Y H:i:s'),
			($this->isChildren ? 'C' : 'P'),
			($this->isRunning ? '+' : '-'),
			$pid,
			call_user_func_array('sprintf', $args),
			str_repeat('-', 20)
		);

		file_put_contents($this->daemonUtilLogFile, $string, FILE_APPEND);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return basename($_SERVER['PHP_SELF']);
	}

	/**
	 * @return int
	 */
	private function getMemoryLimit()
	{
		$val = trim(ini_get('memory_limit'));
		if ($val !== -1) {
			$size = strtolower($val[strlen($val)-1]);
			switch($size) {
				case 'g':
					$val *= 1024; // fall-through
				case 'm':
					$val *= 1024; // fall-through
				case 'k':
					$val *= 1024;
					break;
				default:
					break;
			}
		}

		return $val;
	}

	/**
	 * @param array $eventListeners
	 * @return void
	 */
	protected function addListeners($eventListeners)
	{
		$dispatcher = $this->getEventDispatcher();

		foreach ($eventListeners as $event => $listeners) {
			foreach ($listeners as $listener) {
				$dispatcher->addListener($event, $listener);
			}
		}
	}
}
