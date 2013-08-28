<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

namespace Ustream\DaemonTest;

/**
 * DaemonTestBase
 */
abstract class DaemonTestBase extends \PHPUnit_Framework_TestCase
{
	const COMMON_LOG = '%s/run/daemon_util.log';
	const CONTEXT = 'testcontext';

	/**
	 * @abstract
	 * @return string
	 */
	abstract protected function getLogFile();

	/**
	 * @param string $pidFile
	 * @return void
	 */
	protected function assertPidFileExists($pidFile)
	{
		$this->assertFileExists($pidFile, sprintf('Pidfile %s must exist after start', $pidFile));
	}

	/**
	 * @param int $pid
	 * @param string $pidFile
	 * @return void
	 */
	protected function assertPid($pid, $pidFile)
	{
		$this->assertGreaterThan(0, $pid, sprintf('Pidfile %s must contain process id', $pidFile));
	}

	/**
	 * @return void
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->cleanLog();
		$this->cleanRun();
		$this->makeRun();

		if (!is_writable($this->getRunDir())) {
			$message = $this->red("\nRun directory missing: {$this->getRunDir()}\n");
			echo $message;
			$this->markTestSkipped($message);
		}
	}

	/**
	 * @return void
	 */
	private function cleanLog()
	{
		$logFile   = $this->getLogFile();
		$commonLog = $this->getCommonLog();
		`rm -f $logFile`;
		`rm -f $commonLog`;
	}

	/**
	 * @return void
	 */
	private function cleanRun()
	{
		$runDir = $this->getRunDir();
		`rm $runDir/*.log $runDir/*.pid`;
	}

	/**
	 * @return void
	 */
	private function makeRun()
	{
		$runDir = $this->getRunDir();
		if (!is_dir($runDir)) {
			mkdir($runDir);
		}
	}

	/**
	 * @return string
	 */
	private function getRunDir()
	{
		$runDir = __DIR__ . '/run';
		return $runDir;
	}

	/**
	 * @return string
	 */
	protected function getCommonLog()
	{
		return sprintf(self::COMMON_LOG, __DIR__);
	}

	/**
	 * @param string $string
	 * @return string
	 */
	private function red($string)
	{
		return '\033[01;31m{$text}\033[0m'.$string;
	}
}

?>