<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

/**
 * Configuration for daemons
 */
class Ustream_Daemon_Config
{
	/**
	 * @var string
	 */
	public $runDir = '/var/run';

	/**
	 * @var string
	 */
	public $logDir = '/var/log/custom_php';

	/**
	 * @var string
	 */
	public $customDaemonUtilLogFile;

	/**
	 * @var $sleepBetweenRuns float
	 */
	public $sleepBetweenRuns = 0;

	/**
	 * @var $minimumSleep float
	 */
	public $minimumSleep = 0;

	/**
	 * @var bool
	 */
	public $multipleInstances = false;

	/**
	 * @var int
	 */
	public $instanceNumber = 0;

	/**
	 * @var int percent of used memory before daemon should be stoped
	 */
	public $memoryThreshold = 70;

	/**
	 * @var string
	 */
	public $context;

	/**
	 * @var array
	 */
	public $listeners = array();
}
