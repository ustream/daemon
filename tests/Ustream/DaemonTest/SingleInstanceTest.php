<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

namespace Ustream\DaemonTest;

/**
 * SingleInstanceTest
 */
class SingleInstanceTest extends DaemonTestBase
{
	const PIDFILE = '%s/run/singleinstance-%s.pid';
	const LOGFILE = '%s/run/singleinstance-%s.log';

	/**
	 * @var DaemonControl
	 */
	private $daemonControl;

	/**
	 * @var int
	 */
	private $pid;

	/**
	 * @test
	 */
	public function singleInstanceRunning()
	{
		$this->daemonControl->start();

		RetryAssertion::wait()
			->times(100)
			->sleeping(100, RetryAssertion::MILLISEC)
			->until(array($this, 'checkPid'));

		$this->daemonControl->stop();

		RetryAssertion::wait()
			->times(100)
			->sleeping(100, RetryAssertion::MILLISEC)
			->until(array($this, 'checkPidFileGone'));

		$this->checkLogFiles();
	}

	/**
	 * @return void
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->daemonControl = new DaemonControl(__DIR__.'/singleinstance.php', $this->getPidFileName());
	}

	/**
	 * @return string
	 */
	protected function getLogFile()
	{
		return sprintf(self::LOGFILE, __DIR__, self::CONTEXT);
	}

	private function checkLogFiles()
	{
		$logFileContent = file_get_contents($this->getLogFile());
		$this->assertRegExp('@Single instance@', $logFileContent);

		$this->assertRegExp(
			sprintf(
				'@releaseDaemon unlinking pidFile! pidfileLocation: %s | Pid is %d.@',
				$this->getPidFileName(),
				$this->pid
			),
			file_get_contents($this->getCommonLog())
		);
	}

	public function checkPidFileGone()
	{
		$pidFile = $this->getPidFileName();
		$this->assertFileNotExists($pidFile, sprintf('Pidfile %s must be removed after stop', $pidFile));
	}

	public function checkPid()
	{
		$pidFile = $this->getPidFileName();
		$this->assertPidFileExists($pidFile);
		$this->pid = $this->daemonControl->getPid();
		$this->assertPid($this->pid, $pidFile);
	}

	private function getPidFileName()
	{
		return sprintf(self::PIDFILE, __DIR__, self::CONTEXT);
	}
}

?>