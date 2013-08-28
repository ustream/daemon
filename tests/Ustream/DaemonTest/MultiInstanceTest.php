<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

namespace Ustream\DaemonTest;

/**
 * MultiInstanceTest
 */
class MultiInstanceTest extends DaemonTestBase
{
	const PIDFILE = '%s/run/multiinstance-%s-%s.pid';
	const LOGFILE = '%s/run/multiinstance-%s.log';

	const INSTANCE_0 = 0;
	const INSTANCE_1 = 1;

	/**
	 * @var array
	 */
	private $instances = array(
		self::INSTANCE_0, self::INSTANCE_1
	);

	/**
	 * @var array
	 */
	private $pids = array();

	/**
	 * @var \Ustream\Test\Daemon\MultiInstanceDaemonControl
	 */
	private $daemonControl;

	/**
	 * @test
	 */
	public function twoInstancesRunning()
	{
		$this->daemonControl->startAll();

		RetryAssertion::wait()
			->times(100)
			->sleeping(100, RetryAssertion::MILLISEC)
			->until(array($this, 'checkPids'));

		$this->daemonControl->stopAll();

		RetryAssertion::wait()
			->times(100)
			->sleeping(100, RetryAssertion::MILLISEC)
			->until(array($this, 'checkPidsGone'));

		$this->checkLogFile();
	}

	/**
	 * @return string
	 */
	protected function getLogFile()
	{
		return sprintf(self::LOGFILE, __DIR__, self::CONTEXT);
	}

	/**
	 * @return void
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->daemonControl = new MultiInstanceDaemonControl(
			__DIR__.'/multiinstance.php',
			$this->getPidFileNameTemplate(),
			array(self::INSTANCE_0, self::INSTANCE_1)
		);
	}

	private function checkLogFile()
	{
		$logFileContent = file_get_contents($this->getLogFile());

		foreach ($this->instances as $instanceId) {
			$this->assertRegExp(sprintf('@Instance %d/%d@', $instanceId, count($this->instances)), $logFileContent);
			$this->assertRegExp(
				sprintf(
					'@releaseDaemon unlinking pidFile! pidfileLocation: %s | Pid is %d.@',
					$this->daemonControl->getPidFileName($instanceId),
					$this->pids[$instanceId]
				),
				file_get_contents($this->getCommonLog())
			);
		}
	}

	public function checkPids()
	{
		foreach ($this->instances as $instanceId) {
			$pidFile = $this->daemonControl->getPidFileName($instanceId);
			$this->assertPidFileExists($pidFile);
			$this->pids[$instanceId] = $this->daemonControl->getPid($instanceId);
			$this->assertPid($this->pids[$instanceId], $pidFile);
		}
	}

	public function checkPidsGone()
	{
		foreach ($this->instances as $instanceId) {
			$pidFile = $this->daemonControl->getPidFileName($instanceId);
			$this->assertFileNotExists($pidFile, sprintf('Pidfile %s must not exist after stopped', $pidFile));
		}
	}

	/**
	 * @return string
	 */
	private function getPidFileNameTemplate()
	{
		return sprintf(self::PIDFILE, __DIR__, self::CONTEXT, '%d');
	}
}

?>