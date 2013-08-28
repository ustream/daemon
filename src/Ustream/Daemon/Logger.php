<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

use Psr\Log\LoggerInterface;

/**
 * Ustream_Daemon_Logger
 */
class Ustream_Daemon_Logger implements Psr\Log\LoggerInterface
{
	/**
	 * @var Ustream_Daemon_Daemon
	 */
	private $daemon;

	/**
	 * @var Psr\Log\LoggerInterface
	 */
	private static $instance;

	/**
	 * @static
	 * @return LoggerInterface
	 * @throws InvalidArgumentException
	 */
	public static function getInstance()
	{
		if (self::$instance === null) {
			throw new InvalidArgumentException('No daemon logger instance has been set');
		}
		return self::$instance;
	}

	/**
	 * @static
	 * @param LoggerInterface $instance
	 * @return LoggerInterface
	 */
	public static function setInstance(LoggerInterface $instance)
	{
		self::$instance = $instance;
	}

	/**
	 * @static
	 *
	 * @return void
	 */
	public static function resetInstance()
	{
		self::$instance = null;
	}

	/**
	 * Construct
	 *
	 * @param Ustream_Daemon_Daemon $daemon
	 *
	 * @return Ustream_Daemon_Logger
	 */
	public function __construct(Ustream_Daemon_Daemon $daemon)
	{
		$this->daemon = $daemon;
	}

	/**
	 * Debug message. Should be used/enabled only in developer mode
	 *
	 * @param string $message The log message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function debug($message, array $context = array())
	{
		$this->daemon->writeLog($message);
	}

	/**
	 * Information message. Should not be used too frequently
	 *
	 * @param string $message The log message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function info($message, array $context = array())
	{
		$this->daemon->writeLog($message);
	}

	/**
	 * Notice message, may trigger an E_USER_NOTICE
	 *
	 * @param string $message The log message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function notice($message, array $context = array())
	{
		$this->daemon->writeLog($message);
		trigger_error($message, E_USER_NOTICE);
	}

	/**
	 * Warning message, may trigger an E_USER_WARNING
	 *
	 * @param string $message The log message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function warning($message, array $context = array())
	{
		$this->daemon->writeLog($message);
		trigger_error($message, E_USER_WARNING);
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return null
	 */
	public function emergency($message, array $context = array())
	{
		$this->daemon->writeLog($message);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array  $context
	 * @return null
	 */
	public function alert($message, array $context = array())
	{
		$this->daemon->writeLog($message);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array  $context
	 * @return null
	 */
	public function critical($message, array $context = array())
	{
		$this->daemon->writeLog($message);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array  $context
	 * @return null
	 */
	public function error($message, array $context = array())
	{
		$this->daemon->writeLog($message);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed  $level
	 * @param string $message
	 * @param array  $context
	 * @return null
	 */
	public function log($level, $message, array $context = array())
	{
		$this->daemon->writeLog($message);
	}
}
